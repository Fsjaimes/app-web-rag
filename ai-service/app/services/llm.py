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

IMPORTANTE — Cómo interpretar el contexto:
Los documentos del calendario académico fueron extraídos de un PDF escaneado de dos columnas \
(ACTIVIDAD | FECHA). El símbolo "→" separa la actividad de su fecha correspondiente. Por ejemplo:
  "INICIACIÓN DE CLASES → 10 DE AGOSTO DE 2026"  significa que las clases inician el 10 de agosto de 2026.
  "INSCRIPCIÓN EN LA PÁGINA WEB → DEL 16 DE JULIO DE 2026 AL 23 DE JULIO DE 2026"  son las fechas de inscripción.
Cuando veas "ACTIVIDAD → FECHA", la fecha es la información clave para esa actividad.
Ignora fragmentos de ruido OCR como letras aisladas, símbolos o texto garbled.

Reglas:
- Usa solo la información de los DOCUMENTOS RELEVANTES que se te proporcionan.
- Cuando encuentres "ACTIVIDAD → FECHA" en el contexto, responde con la fecha exacta indicada.
- Si la información no aparece en los documentos, responde: \
"No encontré información sobre ese tema en los documentos oficiales de la UTS. \
Te recomiendo consultar directamente con la oficina correspondiente."
- Responde siempre en español, de forma clara y amable.
- No inventes datos, fechas, requisitos ni procedimientos.
- Cita el nombre del documento fuente cuando sea posible."""


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

    return response.choices[0].message.content
