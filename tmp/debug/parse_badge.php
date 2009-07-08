<?php
$file = "get_badge.txt";

$parser_state = '';

function startElement($parser, $name, $attrs) {
    global $parser_state;
	
	$parser_state = $name;
}

function endElement($parser, $name) {

}

function characterData($parser, $data) {
	global $parser_state;
	global $parsed_date;
    if ($data == '') $parser_state = '';
	if ($parser_state == 'TITLE') $parsed_data[$data] = 0;
	else if ($parser_state == 'DESCRIPTION') $parsed_data
}

$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");

if (!($fp = fopen($file, "r"))) {
    die("could not open XML input");
}

while ($data = fread($fp, 4096)) {
    if (!xml_parse($xml_parser, $data, feof($fp))) {
        die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)));
    }
}
xml_parser_free($xml_parser);
?> 