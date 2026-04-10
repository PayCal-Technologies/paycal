/**
 * PayCalCore - Network Transport Module
 * 
 * Unified request helpers with consistent timeout, error, and auth handling.
 * All requests: 10s timeout (configurable), AbortController, Phantom Wing logging,
 * auth failure detection (401/403 → /login redirect)
 * 
 * IMPORT:
 *   import NetworkModule from '/js/core/network.js';
 */

import PW from '/js/phantomwing/';

const NetworkModule = (() => {

  /**
   * DELETE request with unified error handling.
   * Throws on HTTP error or timeout.
   */
  async function deleteResource(apiBase, endpoint, resourceId, options = {}) {
    if (!endpoint) {
      const msg = 'Invalid endpoint';
      PW.error(`[deleteResource] ${msg}`);
      throw new Error(msg);
    }
    
    const timeoutMs = Number.isFinite(options?.timeoutMs)
      ? Math.max(1000, Number(options.timeoutMs))
      : 10000;
    
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    
    try {
      const response = await fetch(`${apiBase}/${endpoint}/delete/`, {
        method: "DELETE",
        credentials: "include",
        headers: new Headers({
          "Content-Type": "application/json",
          "X-Resource-ID": String(resourceId || '')
        }),
        signal: controller.signal
      });
      clearTimeout(timeout);
      
      if (!response.ok) {
        const errorMsg = `[deleteResource] HTTP ${response.status} - ${response.statusText}`;
        PW.error(errorMsg);
        if (response.status === 401 || response.status === 403) {
          window.location.href = '/login';
        }
        throw new Error(errorMsg);
      }
      
      const json = await response.json();
      return json.data;
    } catch (error) {
      clearTimeout(timeout);
      
      if (error.name === 'AbortError') {
        const msg = `[deleteResource] Request timed out after ${timeoutMs}ms`;
        PW.error(msg);
        throw new Error(msg);
      }
      
      const msg = `[deleteResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  /**
   * GET request returning plain text.
   * Throws on HTTP error or timeout.
   */
  async function readResource(apiBase, endpoint, options = {}) {
    if (!endpoint) {
      const msg = 'Invalid endpoint';
      PW.error(`[readResource] ${msg}`);
      throw new Error(msg);
    }
    
    const timeoutMs = Number.isFinite(options?.timeoutMs)
      ? Math.max(1000, Number(options.timeoutMs))
      : 10000;
    
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    
    try {
      const response = await fetch(`${apiBase}/${endpoint}`, {
        method: "GET",
        credentials: "include",
        signal: controller.signal
      });
      clearTimeout(timeout);
      
      if (!response.ok) {
        const errorMsg = `[readResource] HTTP ${response.status} - ${response.statusText}`;
        PW.error(errorMsg);
        if (response.status === 401 || response.status === 403) {
          window.location.href = '/login';
        }
        throw new Error(errorMsg);
      }
      
      return await response.text();
    } catch (error) {
      clearTimeout(timeout);
      
      if (error.name === 'AbortError') {
        const msg = `[readResource] Request timed out after ${timeoutMs}ms`;
        PW.error(msg);
        throw new Error(msg);
      }
      
      const msg = `[readResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  /**
   * POST request with FormData, returning parsed JSON.data.
   * Throws on HTTP error or timeout.
   */
  async function updateResource(apiBase, endpoint, formData, options = {}) {
    if (!endpoint) {
      const msg = 'Invalid endpoint';
      PW.error(`[updateResource] ${msg}`);
      throw new Error(msg);
    }
    
    if (!(formData instanceof FormData)) {
      const msg = 'Invalid form data (expected FormData)';
      PW.error(`[updateResource] ${msg}`);
      throw new Error(msg);
    }
    
    const timeoutMs = Number.isFinite(options?.timeoutMs)
      ? Math.max(1000, Number(options.timeoutMs))
      : 10000;
    
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    
    try {
      const response = await fetch(`${apiBase}/${endpoint}/update/`, {
        method: "POST",
        credentials: "include",
        body: formData,
        signal: controller.signal
      });
      clearTimeout(timeout);
      
      if (!response.ok) {
        let detail = '';
        try {
          const raw = await response.text();
          if (raw.trim() !== '') {
            try {
              const parsed = JSON.parse(raw);
              if (parsed && typeof parsed === 'object' && parsed.message) {
                detail = String(parsed.message);
              } else {
                detail = raw.slice(0, 240);
              }
            } catch (_parseErr) {
              detail = raw.slice(0, 240);
            }
          }
        } catch (_readErr) {
          detail = '';
        }

        const errorMsg = `[updateResource] HTTP ${response.status} - ${response.statusText}${detail ? ` | ${detail}` : ''}`;
        PW.error(errorMsg);
        
        if (response.status === 401 || response.status === 403) {
          window.location.href = '/login';
        }
        
        throw new Error(errorMsg);
      }
      
      const json = await response.json();
      return json.data;
    } catch (error) {
      clearTimeout(timeout);
      
      if (error.name === 'AbortError') {
        const msg = `[updateResource] Request timed out after ${timeoutMs}ms`;
        PW.error(msg);
        throw new Error(msg);
      }
      
      const msg = `[updateResource] Network error: ${error.message}`;
      PW.error(msg);
      throw new Error(msg);
    }
  }

  return {
    deleteResource,
    readResource,
    updateResource,
  };
})();

export default NetworkModule;
