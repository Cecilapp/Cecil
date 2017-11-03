<!-- Live reload script -->
<script>
var source = new EventSource('http://localhost:8000/watcher');
source.onmessage = function(event) {
  console.log(event)
  if (event.data == 'reload') {
    location.reload(true);
  }
};
</script>