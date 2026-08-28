FROM node:22-alpine

WORKDIR /app

COPY package*.json ./
RUN npm install --ignore-scripts

COPY . .
RUN npm run build

EXPOSE 8888

CMD ["npm", "run", "dev"]
