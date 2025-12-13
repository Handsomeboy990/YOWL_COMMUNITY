# ---------- build stage ----------
    FROM node:20-bullseye AS builder
    
    WORKDIR /app
    
    # Install dependencies (package.json + lock)
    COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* ./
    
    # Install node_modules
    RUN npm ci --no-audit --no-fund
    
    # Copy source
    COPY . .
    
    # Generate Prisma client
    # (safe even if you don't run migrations in build)
    RUN npx prisma generate
    
    # Build Next app (production)
    ENV NODE_ENV=production
    RUN npm run build
    
    # ---------- runtime stage ----------
    FROM node:20-slim AS runner
    
    WORKDIR /app
    
    ENV NODE_ENV=production
    ENV NEXT_TELEMETRY_DISABLED=1
    
    # Create non-root user for better security
    RUN useradd --uid 1000 --create-home appuser
    # Copy only production assets from builder
    COPY --from=builder /app/package.json ./
    COPY --from=builder /app/node_modules ./node_modules
    COPY --from=builder /app/.next ./.next
    COPY --from=builder /app/public ./public
    COPY --from=builder /app/prisma ./prisma
    
    # Copy prisma client (if generated)
    COPY --from=builder /app/node_modules/.prisma ./node_modules/.prisma || true
    
    # Ensure uploads folder exists (will be a mount at runtime)
    RUN mkdir -p /app/public/uploads && chown -R appuser:appuser /app/public/uploads
    
    # Switch to non-root user
    USER appuser
    
    EXPOSE 3000
    
    # Start the Next.js production server
    CMD ["npm", "run", "start"]
    