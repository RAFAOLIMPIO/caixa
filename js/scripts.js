// keep-alive script: pings server every 10 minutes to keep session active
(function(){
  function ping() {
    fetch('keep_alive.php', {credentials: 'include'}).catch(function(){});
  }
  // ping now and then every 10 minutes
  ping();
  setInterval(ping, 10 * 60 * 1000);
})();
