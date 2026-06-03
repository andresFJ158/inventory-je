FROM node:20-alpine
WORKDIR /app
COPY lab-dashboard/ ./
RUN rm -rf .output/server/node_modules && cd .output/server && npm install --omit=dev
EXPOSE 3000
ENV PORT=3000
ENV HOST=0.0.0.0
CMD ["node", ".output/server/index.mjs"]
