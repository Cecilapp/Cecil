
      // Cecil's live reload script
      var eventSource = new EventSource('/watcher');
      eventSource.addEventListener('reload', (event) => {
        let lastChange = event.data;
        let lastReload = sessionStorage.getItem('livereload');
        if (lastChange > lastReload) {
          sessionStorage.setItem('livereload', Math.floor(Date.now() / 1000));
          console.log('reloading');
          location.reload(true);
        }
      }, false);
