<?php defined('SYSPATH') || die('No direct access allowed.'); ?>
<table class="kp-table">
<?php
foreach ($rows as $row):

$class = empty($row['class']) ? '' : ' class="'.$row['class'].'"';
$style = empty($row['style']) ? '' : ' style="'.$row['style'].'"';
?>
	<tr<?php echo $class; echo $style; ?>>
		<?php
        foreach ($columns as $index => $column) {
            $class = empty($column['class']) ? '' : ' class="'.$column['class'].'"';
            $style = empty($column['style']) ? '' : ' style="'.$column['style'].'"';
            $value = $row['data'][$index];
            $value = (is_array($value) || is_object($value)) ? '<pre>'.htmlspecialchars(print_r($value, true), ENT_QUOTES, Kohana::CHARSET).'</pre>' : htmlspecialchars($value, ENT_QUOTES, Kohana::CHARSET);
            echo '<td' . $style . $class . '>' . wordwrap($value, 100, '<br />', true) . '</td>';
        }
        ?>
	</tr>
<?php
endforeach;
?>
</table>
