import re

from openai import OpenAI
from sentence_transformers import SentenceTransformer
from app.config import settings

# ── Clientes (singletons) ────────────────────────────────────────────────────

_nvidia_client: OpenAI | None = None
_embedding_model: SentenceTransformer | None = None


def _get_nvidia_client() -> OpenAI:
    """
    Cliente OpenAI apuntando a la API de NVIDIA NIM.
    Es compatible porque NVIDIA usa el mismo formato de OpenAI.
    """
    global _nvidia_client
    if _nvidia_client is None:
        _nvidia_client = OpenAI(
            api_key=settings.nvidia_api_key,
            base_url=settings.nvidia_api_url,
        )
    return _nvidia_client


def _get_embedding_model() -> SentenceTransformer:
    """
    Modelo de embeddings local — corre dentro del contenedor,
    sin consumir créditos de ninguna API.
    'paraphrase-multilingual-MiniLM-L12-v2' soporta español nativamente.
    """
    global _embedding_model
    if _embedding_model is None:
        _embedding_model = SentenceTransformer(settings.embedding_model)
    return _embedding_model


# ── Embeddings ───────────────────────────────────────────────────────────────

def get_embedding(text: str) -> list[float]:
    """Genera el embedding de un texto."""
    model = _get_embedding_model()
    return model.encode(text, convert_to_numpy=True).tolist()


def get_embeddings(texts: list[str]) -> list[list[float]]:
    """Genera embeddings en batch — más eficiente que llamadas individuales."""
    model = _get_embedding_model()
    return model.encode(texts, convert_to_numpy=True).tolist()


# ── LLM ─────────────────────────────────────────────────────────────────────

SYSTEM_PROMPT = """Eres el asistente virtual oficial de las Unidades Tecnológicas de Santander (UTS).
Tu función es responder preguntas académicas de los estudiantes basándote ÚNICAMENTE \
en los documentos oficiales de la institución que se te proporcionan como contexto.

━━━ INTERPRETACIÓN DEL CONTEXTO ━━━
Los documentos del Calendario Académico fueron extraídos de un PDF escaneado de dos columnas \
(ACTIVIDAD | FECHA). El símbolo "→" separa la actividad de su fecha. Ejemplos:
  • "INICIACIÓN DE CLASES → 10 DE AGOSTO DE 2026"  →  las clases inician el 10 de agosto de 2026.
  • "INSCRIPCIÓN EN LA PÁGINA WEB → DEL 16 DE JULIO DE 2026 AL 23 DE JULIO DE 2026"  →  fechas de inscripción.

Cada fragmento de documento en el contexto tiene un encabezado Markdown con metadatos:
  "#### Calendario UTS"   →  nombre del documento fuente.
  "**Aplica a:** Estudiantes NUEVOS | Modalidad PRESENCIAL"  →  grupo al que aplica el contenido.
Estos encabezados son metadatos de navegación, NO texto del documento. \
Nunca los copies literalmente en tu respuesta; úsalos solo para identificar \
el grupo y mencionarlo con tus propias palabras.

Los encabezados de sección también indican grupos cuando aparecen directamente en el texto:
  • "ESTUDIANTES NUEVOS" o "ESTUDIANTES ANTIGUOS"  →  tipo de estudiante.
  • "MODALIDAD PRESENCIAL", "MODALIDAD VIRTUAL" o "PRESENCIAL Y VIRTUAL"  →  modalidad.
  • "NIVELES TECNOLÓGICO Y UNIVERSITARIO"  →  nivel académico.

Una misma actividad (ej. pago de matrícula, inscripción en página web) puede tener \
fechas DISTINTAS según el tipo de estudiante y la modalidad. Nunca mezcles ni promedies \
información de secciones diferentes.

Ignora fragmentos de ruido OCR (letras aisladas, símbolos, secuencias sin sentido).

━━━ FORMATO DE RESPUESTA OBLIGATORIO ━━━
Cuando la pregunta involucre fechas o actividades del calendario:
1. Identifica en el contexto TODOS los grupos que tienen información relevante \
   (nuevos/antiguos, presencial/virtual, tecnológico/universitario).
2. Si hay MÁS DE UN grupo con información, presenta cada uno POR SEPARADO con su contexto. Ejemplo:
   "📌 Estudiantes NUEVOS — Modalidad Presencial y Virtual:
    Según el Calendario UTS, las inscripciones en la página web son del 16 al 23 de julio de 2026.

    📌 Estudiantes ANTIGUOS — Modalidad Virtual:
    Según el Calendario UTS, la liquidación se descarga en www.uts.edu.co del 6 al 23 de julio de 2026."
3. Si el usuario ya especificó su grupo (ej. "soy estudiante antiguo virtual"), \
   responde solo con la información de ese grupo pero indica que existen otras fechas para otros grupos.
4. Si la pregunta es general y no especifica grupo, enumera todos los grupos encontrados.
5. Cuando solo existe UN grupo relevante, responde directamente mencionando el grupo al inicio. Ejemplo:
   "Para estudiantes NUEVOS (modalidad presencial y virtual), según el Calendario UTS, ..."

━━━ REGLAS ━━━
- Usa ÚNICAMENTE la información de los DOCUMENTOS RELEVANTES proporcionados.
- Cuando encuentres "ACTIVIDAD → FECHA", la fecha es la información clave; inclúyela textualmente.
- No inventes datos, fechas, requisitos ni procedimientos.
- Si no encuentras la información en los documentos, responde:
  "No encontré información sobre ese tema en los documentos oficiales de la UTS. \
Te recomiendo consultar directamente con la oficina correspondiente."
- Responde siempre en español, de forma clara y amigable.
- Cita el nombre del documento fuente al final de la respuesta."""


def chat_completion(
    question: str,
    context: str,
    history: list[dict],
) -> str:
    """
    Llama al LLM de NVIDIA NIM con el prompt RAG completo.

    history: lista de {"role": "user"|"assistant", "content": "..."}
    context: fragmentos de documentos recuperados de ChromaDB
    """
    messages = [{"role": "system", "content": SYSTEM_PROMPT}]

    # Historial previo (máximo últimos 10 para no exceder el contexto del modelo)
    messages.extend(history[-10:])

    user_message = f"""DOCUMENTOS RELEVANTES:
{context}

PREGUNTA:
{question}"""

    messages.append({"role": "user", "content": user_message})

    response = _get_nvidia_client().chat.completions.create(
        model=settings.nvidia_chat_model,
        messages=messages,
        max_tokens=512,
        temperature=0.20,
        top_p=0.70,
    )

    raw = response.choices[0].message.content
    return _clean_response(raw)


# Patrones de metadatos que el modelo no debería repetir pero a veces lo hace
_META_ECHO_RE = re.compile(
    r"(\*{1,2}Aplica a:.*\*{0,2}\n?|"
    r"####\s+Calendario\s+UTS\n?|"
    r"\*{1,2}Calendario\s+UTS\*{0,2}\n?)",
    re.IGNORECASE,
)


def _clean_response(text: str) -> str:
    """Elimina líneas de metadatos que el modelo copió de los encabezados del contexto."""
    cleaned = _META_ECHO_RE.sub("", text)
    # Colapsar líneas en blanco excesivas que queden al limpiar
    cleaned = re.sub(r"\n{3,}", "\n\n", cleaned)
    return cleaned.strip()
