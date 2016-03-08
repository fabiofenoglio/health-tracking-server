<?php

$user = JFactory::getUser();

$params = array(
  "class" =>                H_Document,
  "addRecordUrl" =>         H_UiRouter::getAddDocumentUrl(),
  "userId" =>               $user->id,
	"addRecordText" =>        "Upload a document",
);
?>
<h3>
	<?php echo "Your ".H_UiLang::getRandomAdjective()." documents"; ?>
</h3>
<?php
F_Snippet::show("hal.show.generic_list", $params);