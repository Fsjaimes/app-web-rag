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
# Chunking
# ---------------------------------------------------------------------------

def chunk_text(text: str, chunk_size: int = 1000, overlap: int = 200) -> list[str]:
    """
    Divide el texto en chunks con solapamiento real (sliding window).

    El extractor espacial produce líneas separadas por '\\n' (no '\\n\\n'),
    por lo que primero normaliza ambos tipos de separador a '\\n\\n' para
    que el split por párrafos funcione correctamente.

    Luego usa una ventana deslizante sobre las líneas individuales para
    garantizar solapamiento entre chunks consecutivos.
    """
    # Normalizar: cada línea individual también funciona como separador
    normalized = re.sub(r"\n{2,}", "\n\n", text)
    # Convertir líneas simples del calendario (ACTIVIDAD → FECHA) en párrafos propios
    normalized = re.sub(r"(?<!\n)\n(?!\n)", "\n\n", normalized)

    lines = [ln.strip() for ln in normalized.split("\n\n") if ln.strip()]

    if not lines:
        return []

    chunks: list[str] = []
    start_idx = 0

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
            # Línea individual más larga que chunk_size — incluirla completa
            current_lines = [lines[start_idx]]

        chunks.append("\n".join(current_lines))

        # Avanzar omitiendo las primeras líneas del chunk actual,
        # conservando las últimas `overlap` caracteres como solapamiento.
        consumed_size = 0
        advance = 0
        for ln in current_lines:
            consumed_size += len(ln) + 2
            if consumed_size > chunk_size - overlap:
                break
            advance += 1

        start_idx += max(advance, 1)

    return [c for c in chunks if c]
