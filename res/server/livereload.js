
    <!-- Cecil: live reload script -->
    <script>
    var evtSource = new EventSource('/watcher');
    evtSource.addEventListener('reload', function(e) {
      console.log('reload');
      location.reload(true);
    }, false);
    </script>
    <!-- /Cecil -->
