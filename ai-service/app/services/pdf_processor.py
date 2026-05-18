import io
import re
from collections import defaultdict


def extract_text(file_bytes: bytes) -> str:
    """
    Extrae el texto de un PDF usando pdfplumber con extracción espacial de dos columnas.

    Estrategia por orden de prioridad por página:
      1. extract_tables() — para PDFs con tablas vectoriales reales.
      2. _extract_spatial_columns() — para PDFs escaneados con columnas ACTIVIDAD | FECHA.
      3. extract_text() plano — fallback genérico.
    """
    import pdfplumber

    page_texts: list[str] = []

    with pdfplumber.open(io.BytesIO(file_bytes)) as pdf:
        for page in pdf.pages:
            # 1. Tablas vectoriales reales
            tables = page.extract_tables()
            table_text_parts: list[str] = []
            for table in tables:
                for row in table:
                    cells = [str(c).strip() for c in row if c and str(c).strip()]
                    if cells:
                        table_text_parts.append(" → ".join(cells))

            if table_text_parts:
                page_texts.append("\n".join(table_text_parts))
                continue

            # 2. Extracción espacial de dos columnas (calendario UTS escaneado)
            spatial = _extract_spatial_columns(page)
            if spatial.strip():
                page_texts.append(spatial)
                continue

            # 3. Texto plano como último recurso
            plain = page.extract_text() or ""
            if plain.strip():
                page_texts.append(plain)

    raw = "\n\n".join(page_texts)
    return _clean_text(raw)


# ---------------------------------------------------------------------------
# Extracción espacial de dos columnas
# ---------------------------------------------------------------------------

def _extract_spatial_columns(page) -> str:
    """
    Usa los bounding-boxes de las palabras para reconstruir filas de una
    tabla de dos columnas (izquierda = ACTIVIDAD, derecha = FECHA).

    Algoritmo:
      • Agrupa palabras por fila usando ROW_HEIGHT px de tolerancia.
      • Separa cada fila en columna izquierda / derecha según PAGE_MID.
      • Filtra texto con demasiado ruido OCR.
      • Acumula texto de actividad (izq) y fecha (der) hasta detectar una
        nueva entrada (ambas columnas presentes simultáneamente).
      • Emite líneas "ACTIVIDAD → FECHA" que el LLM puede interpretar.
    """
    words = page.extract_words(keep_blank_chars=False, x_tolerance=3, y_tolerance=3)
    if not words:
        return ""

    PAGE_MID = page.width * 0.53   # divisor entre columna izq y der (~320 px en página de 608)
    ROW_HEIGHT = 13                # altura de fila en puntos tipográficos

    # ¿Tiene estructura de dos columnas?
    right_count = sum(1 for w in words if w["x0"] >= PAGE_MID)
    left_count = sum(1 for w in words if w["x0"] < PAGE_MID)
    if right_count < 4 or left_count < 4:
        # Página de una sola columna — dejar que el caller use extract_text()
        return ""

    # Agrupar palabras por fila
    rows: dict = defaultdict(lambda: {"left": [], "right": []})
    for w in words:
        row_key = round(w["top"] / ROW_HEIGHT) * ROW_HEIGHT
        bucket = "left" if w["x0"] < PAGE_MID else "right"
        rows[row_key][bucket].append(w)

    def words_to_str(word_list: list) -> str:
        return " ".join(w["text"] for w in sorted(word_list, key=lambda w: w["x0"]))

    def is_garbled(text: str) -> bool:
        """Retorna True si el texto es principalmente ruido OCR."""
        if not text or len(text) < 3:
            return False
        valid = sum(1 for c in text if c.isalnum() or c in " áéíóúüñÁÉÍÓÚÜÑ.,:-")
        return valid < len(text) * 0.45

    lines: list[str] = []
    act_parts: list[str] = []
    date_parts: list[str] = []

    def flush():
        """Emite la entrada acumulada al buffer de líneas."""
        if not act_parts and not date_parts:
            return
        act = " ".join(act_parts).strip()
        date = " ".join(date_parts).strip()
        if act and date:
            lines.append(f"{act} → {date}")
        elif act:
            lines.append(act)
        elif date:
            # Fecha sin actividad identificada (continuación de página anterior)
            lines.append(date)

    for row_y in sorted(rows.keys()):
        row = rows[row_y]
        left = words_to_str(row["left"])
        right = words_to_str(row["right"])

        if is_garbled(left):
            left = ""
        if is_garbled(right):
            right = ""

        if not left and not right:
            continue

        if left and right:
            # Ambas columnas presentes → nueva entrada
            flush()
            act_parts = [left]
            date_parts = [right]
        elif left:
            # Solo columna izquierda → continuación del nombre de la actividad
            act_parts.append(left)
        else:
            # Solo columna derecha → continuación de la fecha
            date_parts.append(right)

    flush()
    return "\n".join(lines)


# ---------------------------------------------------------------------------
# Limpieza de texto
# ---------------------------------------------------------------------------

def _clean_text(text: str) -> str:
    """
    Elimina ruido OCR: pies de página institucionales repetitivos,
    códigos QR/certificación y espacios excesivos.
    """
    noise_patterns = [
        r"Consejo\s*→?\s*Académico.*?\n",
        r"Calle de los Estudiantes.*?\n",
        r"PBX:.*?\n",
        r"uts@correo\.uts\.edu\.co\n?",
        r"Código postal.*?\n",
        r"Bucaramanga.*?Colombia.*?\n",
        r"www\.t[iI][tT][sS]\.edu\.co.*?\n",
        r"Página\s+\d+\s+de\s+\d+\n?",
        r"[A-Z0-9]{2,}\.[A-Z0-9]{2,}-[A-Z0-9]+.*?\n",   # códigos QR/cert
        r"iLo hacern?os posib.*?!",
        r"¡\s*Lo hacemos posible!",
        r"Real de Minc.*?\n",
        # Encabezados de tabla garbled (OCR noise de líneas de tabla escaneada)
        r"1[1lI][iI]?\s*[:;{}\[\]|+\\-]{2,}.*?\n",
        r"[{}|\[\]]{3,}.*?\n",
        r"Tecnológicas\s+Unidades\s+de\s+Sant.*?\n",
        r"ub\s+Tecnológicas.*?\n",
        r"uis\s+\.?Tecno.*?\n",
    ]
    for pattern in noise_patterns:
        text = re.sub(pattern, "", text, flags=re.IGNORECASE)

    text = re.sub(r"\n{3,}", "\n\n", text)

    # Normalizar errores OCR frecuentes en texto del calendario UTS
    ocr_fixes = {
        r"MATRiCULA": "MATRÍCULA",
        r"MATRicula": "Matrícula",
        r"MODALiDAD": "MODALIDAD",
        r"MODAliDAD": "MODALIDAD",
        r"ACTiViDAD": "ACTIVIDAD",
        r"hacern?os": "hacemos",
        r"PAGINAWEB": "PÁGINA WEB",
        r"www\.uts,edu\.co": "www.uts.edu.co",
        r"bE\b": "DE",
        r"\bub\s+": "",           # artefacto de logo escaneado
        r"NOVIÉMÉRE": "NOVIEMBRE",
        r"JUUO\b": "JULIO",
        r"cuañdo": "cuando",
    }
    for pattern, replacement in ocr_fixes.items():
        text = re.sub(pattern, replacement, text)

    return text.strip()


# ---------------------------------------------------------------------------
# Chunking con propagación de encabezados de sección
# ---------------------------------------------------------------------------

# Patrones que identifican encabezados de sección del calendario UTS.
# Cuando un chunk empieza sin uno de estos encabezados, se inyecta el último
# encabezado activo para que el modelo siempre sepa a qué grupo aplica la info.
_SECTION_HEADER_RE = re.compile(
    r"ESTUDIANTES\s*(NUEVOS|ANTIGUOS)|"
    r"MODALIDAD\s+(PRESENCIAL\s+Y\s+VIRTUAL|PRESENCIAL|VIRTUAL)|"
    r"NIVELES?\s+TECNOL[ÓO]GICO(\s+Y\s+UNIVERSITARIO)?|"
    r"NIVEL\s+(TECNOL[ÓO]GICO|UNIVERSITARIO)|"
    r"ACTIVIDADES\s+DE\s+TRABAJO\s+DE\s+GRADO|"
    r"CEREMONIAS\s+DE\s+GRADUACI[ÓO]N",
    re.IGNORECASE,
)

# Etiquetas legibles para el prefijo [Contexto:]
_SECTION_LABEL_MAP = [
    ("estudiantes nuevos",          "Estudiantes NUEVOS"),
    ("estudiantes antiguos",        "Estudiantes ANTIGUOS"),
    ("modalidad presencial y virtual", "Modalidad PRESENCIAL Y VIRTUAL"),
    ("modalidad presencial",        "Modalidad PRESENCIAL"),
    ("modalidad virtual",           "Modalidad VIRTUAL"),
    ("niveles tecnológico y universitario", "Niveles TECNOLÓGICO Y UNIVERSITARIO"),
    ("niveles tecnológico",         "Nivel TECNOLÓGICO"),
    ("nivel universitario",         "Nivel UNIVERSITARIO"),
    ("trabajo de grado",            "Actividades de TRABAJO DE GRADO"),
    ("graduación",                  "Ceremonias de GRADUACIÓN"),
]


def _normalise_line(line: str) -> str:
    """
    Une las dos partes de 'ACTIVIDAD → VALOR' en un solo texto para evaluación.
    "ESTUDIANTES → NUEVOS" → "ESTUDIANTES NUEVOS"
    Descarta la parte derecha si contiene una fecha real.
    """
    if re.search(r"→\s*(DEL\b|HASTA\b|AL\b|\d{1,2}\s+DE\b)", line, re.IGNORECASE):
        # La flecha precede a una fecha → eliminar todo desde la flecha
        return re.sub(r"\s*→.*", "", line).strip()
    # No es fecha → unir ambas partes
    return re.sub(r"\s*→\s*", " ", line).strip()


def _is_section_header(line: str) -> bool:
    """
    Retorna True si la línea es un encabezado de sección del calendario
    (tipo de estudiante, modalidad o nivel) y NO una actividad con fecha.
    """
    clean = _normalise_line(line)
    # Los encabezados son cortos (≤ 60 chars) y no mezclan actividad + fecha
    if len(clean) > 70:
        return False
    return bool(_SECTION_HEADER_RE.search(clean))


def _section_label(line: str) -> str:
    """Convierte un encabezado raw en una etiqueta legible para el prefijo de contexto."""
    clean = _normalise_line(line).lower()
    # Normalizar tildes para comparación
    clean = (clean
             .replace("ó", "o").replace("é", "e")
             .replace("á", "a").replace("í", "i").replace("ú", "u"))
    for key, label in _SECTION_LABEL_MAP:
        key_norm = (key
                    .replace("ó", "o").replace("é", "e")
                    .replace("á", "a").replace("í", "i").replace("ú", "u"))
        if key_norm in clean:
            return label
    return _normalise_line(line)


def chunk_text(text: str, chunk_size: int = 1000, overlap: int = 200) -> list[str]:
    """
    Divide el texto en chunks con solapamiento y propagación de sección.

    Estrategia:
    1. Normaliza separadores de línea.
    2. Usa ventana deslizante sobre las líneas para respetar overlap.
    3. Si un chunk nuevo comienza sin un encabezado de sección pero existe
       uno activo de un chunk anterior, lo inyecta como primera línea del
       chunk para que el LLM siempre tenga el contexto (ej. "ESTUDIANTES
       ANTIGUOS / MODALIDAD VIRTUAL") junto a la actividad y fecha.
    """
    # Normalizar separadores
    normalized = re.sub(r"\n{2,}", "\n\n", text)
    normalized = re.sub(r"(?<!\n)\n(?!\n)", "\n\n", normalized)

    lines = [ln.strip() for ln in normalized.split("\n\n") if ln.strip()]

    if not lines:
        return []

    chunks: list[str] = []
    start_idx = 0

    # Estado de sección activo — se actualiza con encabezados jerárquicos:
    #   student_type : "Estudiantes NUEVOS" | "Estudiantes ANTIGUOS" | None
    #   modality     : "Modalidad PRESENCIAL" | "Modalidad VIRTUAL" | ... | None
    #   level        : "Niveles TECNOLÓGICO Y UNIVERSITARIO" | ... | None
    #   special      : "Actividades de TRABAJO DE GRADO" | ... | None
    ctx: dict = {"student": None, "modality": None, "level": None, "special": None}

    def _update_ctx(header_line: str) -> None:
        """Actualiza el contexto de sección según el encabezado detectado."""
        label = _section_label(header_line)
        lbl_low = label.lower()
        if "estudiantes" in lbl_low:
            ctx["student"] = label
            # Cambio de tipo de estudiante → resetear modalidad y especial
            ctx["modality"] = None
            ctx["special"] = None
        elif "modalidad" in lbl_low:
            ctx["modality"] = label
        elif "nivel" in lbl_low:
            ctx["level"] = label
        elif "trabajo de grado" in lbl_low or "graduación" in lbl_low:
            ctx["special"] = label
            ctx["student"] = None
            ctx["modality"] = None

    def _ctx_label() -> str:
        """Devuelve una etiqueta legible del contexto actual."""
        parts = [v for v in [ctx["special"] or ctx["student"], ctx["modality"], ctx["level"]] if v]
        return " | ".join(parts)

    while start_idx < len(lines):
        current_lines: list[str] = []
        current_size = 0
        i = start_idx

        while i < len(lines):
            line = lines[i]
            addition = len(line) + (2 if current_lines else 0)
            if current_size + addition <= chunk_size:
                current_lines.append(line)
                current_size += addition
                i += 1
            else:
                break

        if not current_lines:
            current_lines = [lines[start_idx]]

        # Actualizar el contexto con los encabezados encontrados en este chunk
        for ln in current_lines:
            if _is_section_header(ln):
                _update_ctx(ln)

        # Inyectar prefijo si el chunk no empieza con un encabezado de sección
        # y hay contexto activo y el chunk tiene contenido de calendario
        first_line = current_lines[0]
        ctx_str = _ctx_label()
        if ctx_str and not _is_section_header(first_line):
            has_calendar_content = any("→" in ln for ln in current_lines)
            if has_calendar_content:
                current_lines = [f"[Contexto: {ctx_str}]"] + current_lines

        chunks.append("\n".join(current_lines))

        # Avanzar con solapamiento (no contar la línea de contexto inyectada)
        real_lines = [ln for ln in current_lines if not ln.startswith("[Contexto:")]
        consumed_size = 0
        advance = 0
        for ln in real_lines:
            consumed_size += len(ln) + 2
            if consumed_size > chunk_size - overlap:
                break
            advance += 1

        start_idx += max(advance, 1)

    return [c for c in chunks if c]
