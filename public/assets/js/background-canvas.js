/**
 * Background Canvas Module
 * Animated interactive backgrounds for dark/light themes
 * 
 * Dark Mode: Dot grid with mouse proximity pulse effect
 * Light Mode: Organic gradient mesh with flowing animation
 */
(function () {
  'use strict';

  // Prevent double initialization
  if (window.CatarmanBackground && window.CatarmanBackground._initialized) {
    return;
  }

  // ============================================================================
  // Configuration
  // ============================================================================

  const CONFIG = {
    // Performance
    MOUSE_THROTTLE_MS: 16,        // ~60fps mouse updates
    RESIZE_DEBOUNCE_MS: 100,      // Debounce resize events
    
    // Mobile detection
    MOBILE_BREAKPOINT: 768,
    MOBILE_PARTICLE_RATIO: 0.5,   // 50% particles on mobile
    
    // Dark mode: Dot Grid
    DOT_SPACING: 32,              // Pixels between dots
    DOT_BASE_RADIUS: 1.5,         // Base dot size
    DOT_COLOR: 'rgba(147, 197, 253, 0.22)',      // Subtle blue
    DOT_GLOW_COLOR: 'rgba(147, 197, 253, 0.55)', // Brighter on hover
    DOT_MAX_RADIUS: 4,            // Max size when pulsing
    MOUSE_EFFECT_RADIUS: 150,     // Radius of mouse effect
    WAVE_AMPLITUDE: 2.5,          // Ambient wave movement
    WAVE_FREQUENCY: 0.0015,       // Wave speed
    
    // Light mode: Gradient Mesh
    BLOB_COUNT: 6,                // Number of gradient blobs
    BLOB_MIN_RADIUS: 180,         // Minimum blob size
    BLOB_MAX_RADIUS: 380,         // Maximum blob size
    BLOB_OPACITY_MIN: 0.25,       // Minimum opacity
    BLOB_OPACITY_MAX: 0.45,       // Maximum opacity
    BLOB_DRIFT_SPEED: 0.4,        // Pixels per frame
    BLOB_MOUSE_RADIUS: 200,       // Mouse attraction radius
    BLOB_MOUSE_STRENGTH: 0.08,    // How strongly blobs follow mouse
    BLOB_COLORS: [
      '#DCD1C0',  // Warm cream
      '#E8DCC8',  // Light cream
      '#C7B9A3',  // Muted tan
      '#D8CDBA',  // Soft beige
      '#E5D9C3',  // Pale gold
      '#CFC3AD',  // Dusty cream
    ]
  };

  // ============================================================================
  // Utility Functions
  // ============================================================================

  /**
   * Debounce function execution
   */
  function debounce(fn, delay) {
    let timeoutId = null;
    return function debounced(...args) {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  /**
   * Check if device is mobile
   */
  function isMobile() {
    return window.innerWidth < CONFIG.MOBILE_BREAKPOINT || 
           ('ontouchstart' in window && navigator.maxTouchPoints > 0);
  }

  /**
   * Linear interpolation
   */
  function lerp(start, end, factor) {
    return start + (end - start) * factor;
  }

  /**
   * Simple pseudo-random based on seed (for consistent blob positions)
   */
  function seededRandom(seed) {
    const x = Math.sin(seed * 12.9898) * 43758.5453;
    return x - Math.floor(x);
  }

  // ============================================================================
  // Base Background Canvas Class
  // ============================================================================

  class BackgroundCanvas {
    constructor(canvasId) {
      this.canvas = document.getElementById(canvasId);
      if (!this.canvas) {
        console.warn(`BackgroundCanvas: Canvas #${canvasId} not found`);
        return;
      }
      
      this.ctx = this.canvas.getContext('2d', { alpha: true });
      this.width = 0;
      this.height = 0;
      this.dpr = Math.min(window.devicePixelRatio || 1, 2); // Cap at 2x for performance
      this.mouseX = -1000;
      this.mouseY = -1000;
      this.isMouseInViewport = false;
      this.animationId = null;
      this.isRunning = false;
      this.lastFrameTime = 0;
      
      this._boundHandleResize = debounce(this._handleResize.bind(this), CONFIG.RESIZE_DEBOUNCE_MS);
      this._boundHandleMouseMove = this._handleMouseMove.bind(this);
      this._boundHandleMouseLeave = this._handleMouseLeave.bind(this);
      this._boundHandleVisibilityChange = this._handleVisibilityChange.bind(this);
      this._boundAnimationLoop = this._animationLoop.bind(this);
      
      this._lastMouseMoveTime = 0;
      
      this._setupEventListeners();
      this._handleResize();
    }

    _setupEventListeners() {
      // Resize observer for responsive canvas
      if (typeof ResizeObserver !== 'undefined') {
        this.resizeObserver = new ResizeObserver(this._boundHandleResize);
        this.resizeObserver.observe(document.body);
      } else {
        window.addEventListener('resize', this._boundHandleResize, { passive: true });
      }
      
      // Mouse tracking
      document.addEventListener('mousemove', this._boundHandleMouseMove, { passive: true });
      document.addEventListener('mouseleave', this._boundHandleMouseLeave, { passive: true });
      
      // Visibility API - pause when tab is hidden
      document.addEventListener('visibilitychange', this._boundHandleVisibilityChange);
    }

    _handleResize() {
      if (!this.canvas) return;
      
      this.dpr = Math.min(window.devicePixelRatio || 1, 2);
      this.width = window.innerWidth;
      this.height = window.innerHeight;
      
      // Set canvas size accounting for device pixel ratio
      this.canvas.width = this.width * this.dpr;
      this.canvas.height = this.height * this.dpr;
      
      // Scale context to match DPR
      this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
      
      // Let subclasses regenerate their content
      this.onResize();
    }

    _handleMouseMove(event) {
      // Throttle to ~60fps
      const now = performance.now();
      if (now - this._lastMouseMoveTime < CONFIG.MOUSE_THROTTLE_MS) return;
      this._lastMouseMoveTime = now;
      
      this.mouseX = event.clientX;
      this.mouseY = event.clientY;
      this.isMouseInViewport = true;
    }

    _handleMouseLeave() {
      this.isMouseInViewport = false;
    }

    _handleVisibilityChange() {
      if (document.hidden) {
        this.stop();
      } else if (this._wasRunning) {
        this.start();
      }
    }

    _animationLoop(timestamp) {
      if (!this.isRunning) return;
      
      // Calculate delta time for consistent animation speed
      let deltaTime = timestamp - this.lastFrameTime;
      this.lastFrameTime = timestamp;
      
      // Clear canvas
      this.ctx.clearRect(0, 0, this.width, this.height);
      
      // Let subclass render
      this.render(timestamp, deltaTime);
      
      // Continue loop
      this.animationId = requestAnimationFrame(this._boundAnimationLoop);
    }

    start() {
      if (this.isRunning || !this.canvas) return;
      
      this.isRunning = true;
      this._wasRunning = true;
      this.lastFrameTime = performance.now();
      this.animationId = requestAnimationFrame(this._boundAnimationLoop);
    }

    stop() {
      this._wasRunning = this.isRunning;
      this.isRunning = false;
      if (this.animationId) {
        cancelAnimationFrame(this.animationId);
        this.animationId = null;
      }
    }

    destroy() {
      this.stop();
      
      if (this.resizeObserver) {
        this.resizeObserver.disconnect();
      } else {
        window.removeEventListener('resize', this._boundHandleResize);
      }
      
      document.removeEventListener('mousemove', this._boundHandleMouseMove);
      document.removeEventListener('mouseleave', this._boundHandleMouseLeave);
      document.removeEventListener('visibilitychange', this._boundHandleVisibilityChange);
    }

    // Override in subclasses
    onResize() {}
    render(timestamp, deltaTime) {}
  }

  // ============================================================================
  // Dark Mode: Dot Grid Background
  // ============================================================================

  class DotGridBackground extends BackgroundCanvas {
    constructor() {
      super('bg-canvas-dark');
      if (!this.canvas) return;
      
      this.dots = [];
      this.time = 0;
      
      // Configuration
      this.config = {
        spacing: isMobile() ? 50 : 30,
        baseRadius: 1.5,
        maxRadius: 4,
        baseAlpha: 0.25,
        glowAlpha: 0.6,
        effectRadius: 150,
        waveAmplitude: 3,
        waveSpeed: 0.002,
        dotColor: '147, 197, 253' // RGB for rgba()
      };
      
      this._generateDots();
    }
    
    _generateDots() {
      this.dots = [];
      const { spacing } = this.config;
      
      // Calculate grid with padding
      const cols = Math.ceil(this.width / spacing) + 2;
      const rows = Math.ceil(this.height / spacing) + 2;
      const offsetX = (this.width % spacing) / 2;
      const offsetY = (this.height % spacing) / 2;
      
      for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
          const x = col * spacing + offsetX;
          const y = row * spacing + offsetY;
          // Unique phase based on position for organic wave
          const phase = (x * 0.01) + (y * 0.02) + Math.random() * 0.5;
          this.dots.push({
            baseX: x,
            baseY: y,
            x: x,
            y: y,
            phase: phase,
            radius: this.config.baseRadius,
            alpha: this.config.baseAlpha
          });
        }
      }
    }
    
    onResize() {
      if (!this.config) return; // Guard for base class calling before subclass init
      this.config.spacing = isMobile() ? 50 : 30;
      this._generateDots();
    }
    
    render(timestamp, deltaTime) {
      this.update(deltaTime);
      this.draw();
    }
    
    update(deltaTime) {
      this.time += deltaTime;
      
      const { effectRadius, baseRadius, maxRadius, baseAlpha, glowAlpha, waveAmplitude, waveSpeed } = this.config;
      
      for (const dot of this.dots) {
        // Ambient wave animation
        const wave = Math.sin(this.time * waveSpeed + dot.phase) * waveAmplitude;
        dot.y = dot.baseY + wave;
        
        // Mouse proximity effect
        // Only apply if mouse is in viewport, otherwise use a far away point
        const targetMouseX = this.isMouseInViewport ? this.mouseX : -1000;
        const targetMouseY = this.isMouseInViewport ? this.mouseY : -1000;
        
        const dx = dot.x - targetMouseX;
        const dy = dot.y - targetMouseY;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < effectRadius) {
          // Eased interpolation (ease out cubic)
          const t = 1 - (distance / effectRadius);
          const eased = 1 - Math.pow(1 - t, 3);
          
          dot.radius = baseRadius + (maxRadius - baseRadius) * eased;
          dot.alpha = baseAlpha + (glowAlpha - baseAlpha) * eased;
        } else {
          // Smoothly return to base
          dot.radius += (baseRadius - dot.radius) * 0.1;
          dot.alpha += (baseAlpha - dot.alpha) * 0.1;
        }
      }
    }
    
    draw() {
      const { ctx, width, height } = this;
      const { dotColor } = this.config;
      
      // Clear with dark background
      ctx.fillStyle = '#101A2C';
      ctx.fillRect(0, 0, width, height);
      
      // Draw dots
      for (const dot of this.dots) {
        ctx.beginPath();
        ctx.arc(dot.x, dot.y, dot.radius, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${dotColor}, ${dot.alpha})`;
        ctx.fill();
      }
    }
  }

  // ============================================================================
  // Light Mode: Gradient Mesh Background
  // ============================================================================

  class GradientMeshBackground extends BackgroundCanvas {
    constructor() {
      super('bg-canvas-light');
      if (!this.canvas) return;
      
      this.blobs = [];
      this.time = 0;
      
      // Configuration
      this.config = {
        blobCount: isMobile() ? 4 : 6,
        minRadius: isMobile() ? 150 : 200,
        maxRadius: isMobile() ? 300 : 400,
        driftSpeed: 0.4,
        effectRadius: 200,
        attractionStrength: 0.02,
        // Warm earth tones with enhanced saturation and depth for better visual hierarchy
        colors: [
          { r: 219, g: 189, b: 147, a: 0.65 },  // #DBBD93 - warm tan (foreground)
          { r: 198, g: 154, b: 107, a: 0.50 }, // #C69A6B - rust accent (mid-ground)
          { r: 240, g: 223, b: 202, a: 0.55 },  // #F0DFCA - soft sand (background)
          { r: 168, g: 128, b: 89, a: 0.45 },   // #A88059 - deep brown (depth)
          { r: 235, g: 210, b: 180, a: 0.60 }, // #EBD2B4 - warm cream (emphasis)
          { r: 180, g: 150, b: 120, a: 0.52 }  // #B49678 - mid-brown (balance)
        ]
      };
      
      this._generateBlobs();
    }
    
    _generateBlobs() {
      this.blobs = [];
      const { blobCount, minRadius, maxRadius, colors } = this.config;
      
      for (let i = 0; i < blobCount; i++) {
        const color = colors[i % colors.length];
        this.blobs.push({
          x: Math.random() * this.width,
          y: Math.random() * this.height,
          targetX: 0,
          targetY: 0,
          baseRadius: minRadius + Math.random() * (maxRadius - minRadius),
          radius: 0,
          color: color,
          // Unique phases for organic movement
          phaseX: Math.random() * Math.PI * 2,
          phaseY: Math.random() * Math.PI * 2,
          phaseR: Math.random() * Math.PI * 2,
          speedX: 0.0003 + Math.random() * 0.0002,
          speedY: 0.0004 + Math.random() * 0.0002,
          speedR: 0.0002 + Math.random() * 0.0001
        });
      }
    }
    
    onResize() {
      if (!this.blobs) return; // Guard for base class calling before subclass init
      // Reposition blobs to stay in bounds
      for (const blob of this.blobs) {
        if (blob.x > this.width) blob.x = this.width * 0.8;
        if (blob.y > this.height) blob.y = this.height * 0.8;
      }
    }
    
    render(timestamp, deltaTime) {
      this.update(deltaTime);
      this.draw();
    }
    
    update(deltaTime) {
      this.time += deltaTime;
      
      const { driftSpeed, effectRadius, attractionStrength } = this.config;
      
      for (let i = 0; i < this.blobs.length; i++) {
        const blob = this.blobs[i];
        
        // Varied drift speeds per blob for organic, layered motion
        const individualDriftSpeed = driftSpeed * (0.7 + i * 0.1); // Each blob drifts at slightly different rate
        
        // Ambient drift using combined sine waves (perlin-like)
        const driftX = Math.sin(this.time * blob.speedX + blob.phaseX) * individualDriftSpeed;
        const driftY = Math.sin(this.time * blob.speedY + blob.phaseY) * individualDriftSpeed;
        
        // Size morphing with more pronounced variation
        const radiusMod = Math.sin(this.time * blob.speedR + blob.phaseR) * 25; // Increased from 20 for more dynamic feel
        blob.radius = blob.baseRadius + radiusMod;
        
        // Calculate target position (with drift)
        blob.targetX = blob.x + driftX;
        blob.targetY = blob.y + driftY;
        
        // Enhanced mouse attraction with easing for more responsive feel
        const dx = this.mouseX - blob.x;
        const dy = this.mouseY - blob.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < effectRadius && distance > 0 && this.isMouseInViewport) {
          // Ease-out cubic for smoother attraction curve
          const t = 1 - (distance / effectRadius);
          const eased = 1 - Math.pow(1 - t, 3);
          const attraction = eased * attractionStrength * 1.5; // Enhanced attraction strength
          blob.targetX += dx * attraction;
          blob.targetY += dy * attraction;
        }
        
        // Smooth movement toward target (slightly faster for better responsiveness)
        blob.x += (blob.targetX - blob.x) * 0.03; // Increased from 0.02
        blob.y += (blob.targetY - blob.y) * 0.03;
        
        // Keep blobs in bounds (with margin)
        const margin = blob.radius * 0.5;
        if (blob.x < -margin) blob.x = this.width + margin;
        if (blob.x > this.width + margin) blob.x = -margin;
        if (blob.y < -margin) blob.y = this.height + margin;
        if (blob.y > this.height + margin) blob.y = -margin;
      }
    }
    
    draw() {
      const { ctx, width, height } = this;
      
      // Clear with light background - much lighter base so blobs are visible
      const bgGradient = ctx.createLinearGradient(0, 0, width, height);
      bgGradient.addColorStop(0, '#FBF9F6');     // Nearly white with subtle warmth
      bgGradient.addColorStop(0.5, '#F9F7F4');   // Neutral off-white
      bgGradient.addColorStop(1, '#F7F5F2');    // Slightly warmer off-white
      ctx.fillStyle = bgGradient;
      ctx.fillRect(0, 0, width, height);
      
      // Draw blobs with enhanced radial gradients for better depth and luminosity
      for (const blob of this.blobs) {
        const gradient = ctx.createRadialGradient(
          blob.x, blob.y, 0,
          blob.x, blob.y, blob.radius
        );
        
        const { r, g, b, a } = blob.color;
        // Enhanced gradient stops: brighter center, smoother falloff
        gradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, ${a})`);
        gradient.addColorStop(0.3, `rgba(${r}, ${g}, ${b}, ${a * 0.7})`);
        gradient.addColorStop(0.7, `rgba(${r}, ${g}, ${b}, ${a * 0.25})`);
        gradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0)`);
        
        ctx.beginPath();
        ctx.arc(blob.x, blob.y, blob.radius, 0, Math.PI * 2);
        ctx.fillStyle = gradient;
        ctx.fill();
      }
    }
  }

  // ============================================================================
  // Background Manager
  // ============================================================================

  class BackgroundManager {
    constructor() {
      this.darkBackground = null;
      this.lightBackground = null;
      this.currentTheme = null;
      this.isInitialized = false;
      
      this._boundHandleThemeChange = this._handleThemeChange.bind(this);
    }

    init() {
      if (this.isInitialized) return;
      
      // Create background instances (animations run regardless of reduced-motion preference)
      this.darkBackground = new DotGridBackground();
      this.lightBackground = new GradientMeshBackground();
      
      // Listen for theme changes
      window.addEventListener('theme:changed', this._boundHandleThemeChange);
      
      // Get initial theme
      this.currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      
      // Start appropriate background
      this._activateTheme(this.currentTheme);
      
      this.isInitialized = true;
    }

    _handleThemeChange(event) {
      const newTheme = event.detail?.theme;
      if (newTheme && newTheme !== this.currentTheme) {
        this._activateTheme(newTheme);
      }
    }

    _activateTheme(theme) {
      this.currentTheme = theme;
      
      if (theme === 'dark') {
        // Start dark, stop light
        this.lightBackground?.stop();
        this.darkBackground?.start();
      } else {
        // Start light, stop dark
        this.darkBackground?.stop();
        this.lightBackground?.start();
      }
    }

    setTheme(theme) {
      this._activateTheme(theme);
    }

    /**
     * Force initialization (bypasses reduced motion check)
     * For testing/debugging purposes
     */
    forceInit() {
      if (this.isInitialized) {
        this.destroy();
      }
      
      // Create background instances
      this.darkBackground = new DotGridBackground();
      this.lightBackground = new GradientMeshBackground();
      
      // Listen for theme changes
      window.addEventListener('theme:changed', this._boundHandleThemeChange);
      
      // Get initial theme
      this.currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      
      // Start appropriate background
      this._activateTheme(this.currentTheme);
      
      this.isInitialized = true;
    }

    destroy() {
      window.removeEventListener('theme:changed', this._boundHandleThemeChange);
      this.darkBackground?.destroy();
      this.lightBackground?.destroy();
      this.isInitialized = false;
    }
  }

  // ============================================================================
  // Initialization
  // ============================================================================

  const manager = new BackgroundManager();

  // Initialize when DOM is ready
  function initBackground() {
    manager.init();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBackground, { once: true });
  } else {
    // Small delay to ensure canvas elements are in DOM
    requestAnimationFrame(initBackground);
  }

  // Export for external access
  window.CatarmanBackground = {
    _initialized: true,
    manager: manager,
    setTheme: (theme) => manager.setTheme(theme),
    destroy: () => manager.destroy(),
    restart: () => {
      manager.destroy();
      manager.init();
    }
  };

})();