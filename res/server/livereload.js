
    <!-- Cecil: live reload script -->
    <script>
    var evtSource = new EventSource('http://localhost:8000/watcher');
    evtSource.addEventListener('reload', function(e) {
      console.log('reload');
      location.reload(true);
    }, false);
    </script>
    <!-- /Cecil -->
