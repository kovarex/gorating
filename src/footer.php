<div style="position:screen;bottom:0px;">
  <hr/>
  Friendly go rating made by kovarex.
<?php

  if (isset($pageStart))
  {
    $pageEnd = microtime(true);
    $execution_time = ($pageEnd - $pageStart);
    echo " - Page generated in ".round($execution_time, 4)." seconds";
  }
?>
</div>
</div>
</body>
</html>
