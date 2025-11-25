FROM node:18-alpine

WORKDIR /app

# Copy package files first
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the source
COPY . .

EXPOSE 5173

# Run Vite dev server in the container
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
