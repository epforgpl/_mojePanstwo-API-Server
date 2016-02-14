<?

	return $this->DB->selectValue("SELECT `content` FROM `news` WHERE `id`='" . addslashes( $id ) . "'");