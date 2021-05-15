<?php

class SnapshotManagerException extends Exception
{
  public function __construct($message, $code = 0, Exception $previous = null) {
      parent::__construct($message, $code, $previous);
  }
}

class SnapshotManager extends PictureManager
{
  function __construct($picture_manageable_item)
  {
    parent::__construct($picture_manageable_item, 'snapshot');
  }
}

 ?>
