#!/bin/bash

countdown() {
    local seconds=$1
    while [ $seconds -gt 0 ]; do
        sleep 1
        ((seconds--))
    done
}

while true; do

  ENV_FILE="$(pwd)/.env"

  if [ -f "$ENV_FILE" ]; then
      source "$ENV_FILE"
  else
      echo ".env файл не найден!"
      exit 1
  fi

  QUEUE_LENGTH=$(curl -u guest:guest -s "http://localhost:15672/api/queues/%2F/$RABBITMQ_QUEUE_NAME" | jq '.messages')

  WORKERS=$(docker-compose ps -q worker | wc -l)

  if [ "$QUEUE_LENGTH" -gt "$QUEUE_LENGTH_THRESHOLD_HIGH" ]; then
      NEW_WORKERS=$(($WORKERS + 1))
      if [ "$NEW_WORKERS" -le "$MAX_WORKERS" ]; then
          echo "Очередь большая, добавляем количество воркеров, новое количество: $NEW_WORKERS"
          docker-compose up --scale worker=$NEW_WORKERS -d
      else
          echo "Достигнут максимальный лимит воркеров ($MAX_WORKERS)."
      fi

  elif [ "$QUEUE_LENGTH" -lt "$QUEUE_LENGTH_THRESHOLD_LOW" ]; then
      NEW_WORKERS=$(($WORKERS - 1))
      if [ "$NEW_WORKERS" -ge "$MIN_WORKERS" ]; then
          echo "Очередь маленькая, уменьшаем количество воркеров, новое количество: $NEW_WORKERS"
          docker-compose up --scale worker=$NEW_WORKERS -d
      else
          echo "Минимальное количество воркеров ($MIN_WORKERS) уже достигнуто."
      fi
  else
      echo "Очередь в норме, масштабировать не нужно."
  fi

  countdown 60

done
