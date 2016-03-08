<?php

if (F_Input::exists("lmsroute")) {
F_Input::set("md", str_replace("/", ".", F_Input::getRaw("lmsroute", "user.settings")));
}
else if (F_Input::getRaw("view") == "dashboard") {
F_Input::set("md", "user.settings");
}

F_SimplecomponentDispatcher::process();
return;