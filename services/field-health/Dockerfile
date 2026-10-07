FROM python:3.12-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY main.py .

# Cloud Run sets PORT; 8080 elsewhere.
ENV PORT=8080
CMD exec uvicorn main:app --host 0.0.0.0 --port ${PORT} --workers 2
