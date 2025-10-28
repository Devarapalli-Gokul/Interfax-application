// Helper function to check if we should use InterFAX API mode
function shouldUseInterfaxApi() {
  // Default to InterFAX API mode, can be overridden with environment variable
  return import.meta.env.VITE_USE_INTERFAX_API !== 'false';
}

export async function api(path, { method = 'GET', headers = {}, body } = {}) {
  const token = localStorage.getItem('auth_token');
  
  const opts = { 
    method, 
    headers: { 
      'Accept': 'application/json', 
      ...headers 
    } 
  };
  
  if (token) {
    opts.headers['Authorization'] = `Bearer ${token}`;
  }
  
  if (body && !(body instanceof FormData)) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  } else if (body) {
    opts.body = body;
  }
  
  const res = await fetch(`/api${path}`, opts);
  const ctype = res.headers.get('content-type') || '';
  const isJson = ctype.includes('application/json');
  const isPdf = ctype.includes('application/pdf');
  const isImage = ctype.includes('image/');
  
  console.log('API Response:', {
    path,
    status: res.status,
    contentType: ctype,
    isJson,
    isPdf,
    isImage,
    contentLength: res.headers.get('content-length')
  });
  
  let data;
  if (isJson) {
    data = await res.json();
  } else {
    data = await res.blob();
    console.log('Blob received:', {
      size: data.size,
      type: data.type,
      path: path
    });
    
    // For PDFs, verify the blob content
    if (isPdf && data.size > 0) {
      const arrayBuffer = await data.arrayBuffer();
      const uint8Array = new Uint8Array(arrayBuffer);
      const header = String.fromCharCode.apply(null, uint8Array.slice(0, 4));
      console.log('PDF header check:', header, 'Is PDF:', header === '%PDF');
      
      // Recreate the blob with the correct type
      data = new Blob([arrayBuffer], { type: 'application/pdf' });
    }
  }
  
  if (!res.ok) {
    throw new Error(isJson ? (data.message || res.statusText) : res.statusText);
  }
  
  return data;
}

// Auth API
export const authApi = {
  login: (credentials) => api('/login', { method: 'POST', body: credentials }),
  logout: () => api('/logout', { method: 'POST' }),
  user: () => api('/user'),
};

// Fax API
export const faxApi = {
  getInbound: (page = 1, perPage = 10) => {
    const params = new URLSearchParams({ page, per_page: perPage });
    return shouldUseInterfaxApi() 
      ? api(`/faxes/interfax/inbound?${params}`) 
      : api(`/faxes/inbound?${params}`);
  },
  getOutbound: (page = 1, perPage = 10) => {
    const params = new URLSearchParams({ page, per_page: perPage });
    return shouldUseInterfaxApi() 
      ? api(`/faxes/interfax/outbound?${params}`) 
      : api(`/faxes/outbound?${params}`);
  },
  getInboundFax: (id) => api(`/faxes/inbound/${id}`),
  getOutboundFax: (id) => api(`/faxes/outbound/${id}`),
  getInboundContent: (id, inline = false) => {
    if (shouldUseInterfaxApi()) {
      return api(`/faxes/interfax/inbound/${id}/content${inline ? '?inline=1' : ''}`);
    }
    return api(`/faxes/inbound/${id}/content${inline ? '?inline=1' : ''}`);
  },
  getOutboundContent: (id, inline = false) => {
    if (shouldUseInterfaxApi()) {
      // Use InterFAX API endpoint for outbound faxes, just like inbound faxes
      // Both now get content directly from InterFAX API according to documentation
      return api(`/faxes/interfax/outbound/${id}/content${inline ? '?inline=1' : ''}`);
    }
    // Fallback to local database (only when not in real-time mode)
    return api(`/faxes/outbound/${id}/content${inline ? '?inline=1' : ''}`);
  },
  sendFax: (formData) => api('/faxes/outbound', { method: 'POST', body: formData }),
  cancelFax: (id) => api(`/faxes/outbound/${id}/cancel`, { method: 'POST' }),
};
