
      // Cecil's live reload script
      var evtSource = new EventSource('/watcher');
      evtSource.addEventListener('reload', function(e) {
        let lastChange = e.data;
        let lastReload = sessionStorage.getItem('livereload');
        if (lastChange > lastReload) {
          sessionStorage.setItem('livereload', Math.floor(Date.now() / 1000));
          console.log('reload now!');
          location.reload(true);
        }
      }, false);
