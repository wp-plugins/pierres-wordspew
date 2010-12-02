<style type="text/css">
#chatoutput { 
	border: 1px solid #<?php echo $shout_opt['name_color']; ?>; 
	color: #<?php echo $shout_opt['text_color']; ?>;
	background: #<?php echo $shout_opt['fade_to']; ?>;
}
#chatoutput span { 
	color: #<?php echo $shout_opt['name_color']; ?>;
}
#chatoutput li a { 
	color: #<?php echo $shout_opt['name_color']; ?>;
}
#chatoutput li span a {
	border-bottom: 1px dotted #<?php echo $shout_opt['name_color']; ?>;
}
#chatoutput ul#outputList li {
	color: #<?php echo $shout_opt['text_color']; ?>;
	min-height: <?php echo $shout_opt['avatar_size']; ?>px;
}
#lastMessage {
	border-bottom: 2px dotted #<?php echo $shout_opt['fade_from']; ?>;
}
#usersOnline {
	color: #<?php echo $shout_opt['name_color']; ?>; 
}
tr.bg td {
	border-bottom: 1px dashed #<?php echo $shout_opt['fade_from']; ?>;
}
tr.bg:hover td, tr.bg:hover td a {
	 background: #<?php echo $shout_opt['name_color']; ?>;
	 color: #<?php echo $shout_opt['fade_to']; ?>;
}
</style>