<?php
echo "Save Order";
// Hardcoded → cannot translate

__('Hello', 'myplugin');
esc_html__('Hello', 'myplugin');
esc_attr__('Name', 'myplugin');

// These return a string not echo at runtime 

_e('Hello', 'myplugin');
esc_html_e('Hello', 'myplugin');
// These echo at runtime 

printf(
    _n('%d order', '%d orders', $count, 'myplugin'),
    $count
);
// for plural like if there is 1 order then it shows 1 order for plural it shows 2 orders 

echo __('Hello ', 'myplugin') . $name;
// instead of doing ths do this 

printf(
    esc_html__('Hello %s', 'myplugin'),
    esc_html($name)
);


// textdomain is mandatory for the translation 

// __() → lookup → .mo file → return translated string
// wp i18n make-pot . languages/myplugin.pot
// .pot → .po
// .po → .mo
// WordPress loads .mo 