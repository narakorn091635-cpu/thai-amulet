const localtunnel = require('localtunnel');
(async () => {
  const tunnel = await localtunnel({ port: 8000 });
  console.log('PUBLIC_URL:' + tunnel.url);
  tunnel.on('close', () => {
    console.log('tunnel closed');
  });
})();
