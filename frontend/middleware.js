export const config = {
  // Hanya jalankan middleware ini untuk halaman detail undangan
  matcher: '/invitation/:id*',
};

export default async function middleware(request) {
  const url = new URL(request.url);
  const userAgent = request.headers.get('user-agent') || '';
  const isCrawler = /whatsapp|facebookexternalhit|twitterbot|slackbot|telegrambot/i.test(userAgent);

  if (isCrawler) {
    const segments = url.pathname.split('/');
    const id = segments[segments.length - 1];

    // Mengambil URL API secara dinamis dari Environment Variable Vercel
    const apiUrl = process.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1';
    const targetUrl = `${apiUrl}/events/${id}/invitation/crawler`;

    try {
      const response = await fetch(targetUrl, {
        headers: {
          'User-Agent': userAgent
        }
      });
      
      if (response.ok) {
        const html = await response.text();
        return new Response(html, {
          headers: {
            'Content-Type': 'text/html; charset=utf-8'
          }
        });
      }
    } catch (e) {
      console.error('Error fetching crawler HTML from API:', e);
    }
  }

  // Jika bukan crawler atau jika fetch gagal, biarkan request berlanjut
  // Vercel akan otomatis merender index.html Vue SPA seperti biasa.
}
