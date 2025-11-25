# --- Build stage ---
FROM node:18-alpine AS builder

WORKDIR /app

# Copy package files first
COPY package*.json ./

# Install dependencies cleanly
RUN npm ci || npm install

# Copy the rest of the source
COPY . .

# Build
RUN npm run build

# --- Production stage ---
FROM node:18-alpine

WORKDIR /app

# Copy built assets only
COPY --from=builder /app/dist ./dist

# Install only what's required to run vite preview
COPY package*.json ./
RUN npm install --omit=dev

EXPOSE 5173

CMD ["npm", "run", "preview", "--", "--host", "0.0.0.0", "--port", "5173"]
