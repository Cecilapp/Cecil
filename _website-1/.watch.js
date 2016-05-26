<script>
var HttpClient = function() {
  this.get = function(aUrl, aCallback) {
    anHttpRequest = new XMLHttpRequest();
    anHttpRequest.onreadystatechange = function() {
      if (anHttpRequest.readyState == 4 && anHttpRequest.status == 200)
        aCallback(anHttpRequest.responseText);
    }
    anHttpRequest.open("GET", aUrl, true);
    anHttpRequest.send(null);
  }
}
aClient = new HttpClient();
var i = setInterval(function(){
  aClient.get('http://localhost:8000/watcher', function(answer) {
    if (answer == 'true') {
      location.reload(true);
    } else if (answer == 'stop') {
      clearInterval(i);
    }
  });
}, 500); // 0.5 s
</script>