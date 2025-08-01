/**
 * Service Worker - PWA支持
 * 提供离线缓存和性能优化
 */

const CACHE_NAME = 'homepage-v1.0.0';
const STATIC_CACHE = 'static-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-v1.0.0';

// 需要缓存的静态资源
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/css/main.css',
    '/css/theme.css',
    '/css/responsive.css',
    '/js/main.js',
    '/js/navigation.js',
    '/js/animations.js',
    '/js/particles.js',
    '/config/links.json',
    '/config/default.json',
    '/assets/images/avatar.svg',
    '/assets/images/default-avatar.svg',
    '/assets/icons/favicon.svg',
    '/pwa/manifest.json'
];

// 动态缓存的URL模式
const DYNAMIC_CACHE_PATTERNS = [
    /^https:\/\/fonts\.googleapis\.com/,
    /^https:\/\/fonts\.gstatic\.com/,
    /^https:\/\/cdn\.jsdelivr\.net/
];

// 安装事件 - 缓存静态资源
self.addEventListener('install', event => {
    console.log('Service Worker: 安装中...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Service Worker: 缓存静态资源');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('Service Worker: 安装完成');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker: 安装失败', error);
            })
    );
});

// 激活事件 - 清理旧缓存
self.addEventListener('activate', event => {
    console.log('Service Worker: 激活中...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('Service Worker: 删除旧缓存', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: 激活完成');
                return self.clients.claim();
            })
    );
});

// 拦截请求 - 缓存策略
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // 只处理GET请求
    if (request.method !== 'GET') {
        return;
    }
    
    // 跳过chrome-extension和其他协议
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    event.respondWith(
        handleRequest(request)
    );
});

/**
 * 处理请求的主要逻辑
 */
async function handleRequest(request) {
    const url = new URL(request.url);
    
    try {
        // 1. 静态资源 - 缓存优先策略
        if (isStaticAsset(request)) {
            return await cacheFirst(request, STATIC_CACHE);
        }
        
        // 2. API请求 - 网络优先策略
        if (isApiRequest(request)) {
            return await networkFirst(request, DYNAMIC_CACHE);
        }
        
        // 3. 外部资源 - 缓存优先策略
        if (isExternalResource(request)) {
            return await cacheFirst(request, DYNAMIC_CACHE);
        }
        
        // 4. 其他请求 - 网络优先策略
        return await networkFirst(request, DYNAMIC_CACHE);
        
    } catch (error) {
        console.error('Service Worker: 请求处理失败', error);
        
        // 返回离线页面或默认响应
        if (request.destination === 'document') {
            return await caches.match('/index.html');
        }
        
        // 返回空响应
        return new Response('', { status: 408, statusText: 'Request Timeout' });
    }
}

/**
 * 缓存优先策略
 */
async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        // 后台更新缓存
        updateCache(request, cache);
        return cachedResponse;
    }
    
    // 缓存中没有，从网络获取
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
}

/**
 * 网络优先策略
 */
async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // 网络失败，尝试从缓存获取
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        throw error;
    }
}

/**
 * 后台更新缓存
 */
async function updateCache(request, cache) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
    } catch (error) {
        // 静默失败，不影响用户体验
        console.log('Service Worker: 后台更新失败', error);
    }
}

/**
 * 判断是否为静态资源
 */
function isStaticAsset(request) {
    const url = new URL(request.url);
    
    return STATIC_ASSETS.some(asset => {
        return url.pathname === asset || url.pathname.endsWith(asset);
    });
}

/**
 * 判断是否为API请求
 */
function isApiRequest(request) {
    const url = new URL(request.url);
    
    return url.pathname.startsWith('/api/') || 
           url.pathname.startsWith('/config/');
}

/**
 * 判断是否为外部资源
 */
function isExternalResource(request) {
    const url = new URL(request.url);
    
    return DYNAMIC_CACHE_PATTERNS.some(pattern => {
        return pattern.test(url.href);
    });
}

// 消息处理 - 支持手动缓存更新
self.addEventListener('message', event => {
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'CACHE_UPDATE':
            updateAllCaches();
            break;
            
        case 'CACHE_CLEAR':
            clearAllCaches();
            break;
            
        default:
            console.log('Service Worker: 未知消息类型', type);
    }
});

/**
 * 更新所有缓存
 */
async function updateAllCaches() {
    try {
        const cache = await caches.open(STATIC_CACHE);
        
        await Promise.all(
            STATIC_ASSETS.map(async (asset) => {
                try {
                    const response = await fetch(asset);
                    if (response.ok) {
                        await cache.put(asset, response);
                    }
                } catch (error) {
                    console.error('Service Worker: 更新缓存失败', asset, error);
                }
            })
        );
        
        console.log('Service Worker: 缓存更新完成');
    } catch (error) {
        console.error('Service Worker: 缓存更新失败', error);
    }
}

/**
 * 清理所有缓存
 */
async function clearAllCaches() {
    try {
        const cacheNames = await caches.keys();
        
        await Promise.all(
            cacheNames.map(cacheName => caches.delete(cacheName))
        );
        
        console.log('Service Worker: 所有缓存已清理');
    } catch (error) {
        console.error('Service Worker: 清理缓存失败', error);
    }
}

// 错误处理
self.addEventListener('error', event => {
    console.error('Service Worker: 全局错误', event.error);
});

self.addEventListener('unhandledrejection', event => {
    console.error('Service Worker: 未处理的Promise拒绝', event.reason);
});

console.log('Service Worker: 脚本加载完成');