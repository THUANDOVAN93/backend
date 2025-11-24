FROM node:20-alpine

RUN apk add --no-cache \
    python3 \
    make \
    g++ \
    git \
    curl

WORKDIR /app

COPY . .

RUN npm install

RUN npm run cleanup || true
RUN npm run build

RUN ls -la .output/ && \
    test -f .output/server/index.mjs || (echo "Build failed: index.mjs not found" && exit 1)

EXPOSE 3000

#ENV NODE_ENV=production
#ENV HOST=0.0.0.0
#ENV PORT=3000
#ENV NITRO_HOST=0.0.0.0
#ENV NITRO_PORT=3000

CMD ["npm", "run", "serve"]
