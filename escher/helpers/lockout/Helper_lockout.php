<?php

abstract class Helper_lockout extends Helper {
	abstract function lock($resource,$entity=NULL);
	abstract function unlock($resource);
	abstract function isLocked($resource);
}