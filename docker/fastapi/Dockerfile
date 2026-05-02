FROM python:3.11-slim

# Evita archivos .pyc y fuerza logs en tiempo real en Docker
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
ENV TZ=America/Bogota

WORKDIR /app

# build-essential es necesario para compilar algunas dependencias de Python
RUN apt-get update && apt-get install -y \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Primero copia solo requirements para aprovechar cache de Docker.
# Si no cambia requirements.txt, Docker no reinstala todo en cada build.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8000

# --reload activa hot reload — cambios en el código se reflejan sin reconstruir
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]