<?php defined('SYSPATH') || die('No direct script access.') ?>
<?php
print($open);

// Not sure what to do with these, but at least show that we received them.
if ($class) {
    print "<!-- unused class in form.html.php: $class -->";
}
if ($title) {
    print $title;
}

if (!function_exists('DrawForm')) {
    function DrawForm($inputs, $level=1)
    {
        $error_messages = [];
        $prefix = str_repeat('  ', $level);
        $haveGroup = false;
        // On the first level, make sure we have a group if not add the <ul> tag now
        if (1 == $level) {
            foreach ($inputs as $input) {
                $haveGroup |= 'group' == $input->type;
            }
            if (!$haveGroup) {
                print "$prefix<ul>\n";
            }
        }

        foreach ($inputs as $input) {
            if ('group' == $input->type) {
                print "$prefix<fieldset>\n";
                print "$prefix  <legend>{$input->label}</legend>\n";
                print "$prefix  <ul>\n";

                DrawForm($input->inputs, $level + 2);
                print "$prefix  </ul>\n";

                // Since hidden fields can only have name and value attributes lets just render it now
                $hidden_prefix = "$prefix    ";
                foreach ($input->hidden as $hidden) {
                    print "$prefix  {$hidden->render()}\n";
                }
                print "$prefix</fieldset>\n";
            } elseif ('script' == $input->type) {
                print $input->render();
            } else {
                if ($input->error_messages()) {
                    print "$prefix<li class=\"g-error\">\n";
                } else {
                    print "$prefix<li>\n";
                }

                if ($input->label()) {
                    print "$prefix  {$input->label()}\n";
                }
                print "$prefix  {$input->render()}\n";
                if ($input->message()) {
                    print "$prefix  <p>{$input->message()}</p>\n";
                }
                if ($input->error_messages()) {
                    foreach ($input->error_messages() as $error_message) {
                        print "$prefix  <p class=\"g-message g-error\">\n";
                        print "$prefix    $error_message\n";
                        print "$prefix  </p>\n";
                    }
                }
                print "$prefix</li>\n";
            }
        }
        if (1 == $level && !$haveGroup) {
            print "$prefix</ul>\n";
        }
    }
}
DrawForm($inputs);

print($close);
?>
