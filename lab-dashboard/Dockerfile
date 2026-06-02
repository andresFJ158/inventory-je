FROM node:22-alpine AS builder

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

ARG PROXY_AJAX_URL=http://frontend/ajax/**
ARG PROXY_API_URL=http://api/**

ENV PROXY_AJAX_URL=$PROXY_AJAX_URL
ENV PROXY_API_URL=$PROXY_API_URL

RUN npm run build

FROM node:22-alpine AS runner

WORKDIR /app

COPY --from=builder /app/.output ./.output
COPY --from=builder /app/package.json ./package.json

ENV PORT=3000
ENV HOST=0.0.0.0
ENV NODE_ENV=production

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
