<?php

return $this->DB->selectAssocs("
    SELECT `tematy`.`q`
    FROM `objects-pages_tags`
    RIGHT JOIN `tematy` ON `tematy`.`id` = `objects-pages_tags`.`tag_id`
    WHERE `objects-pages_tags`.`object_id` = {$id}
");