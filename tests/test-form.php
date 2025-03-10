<?php
if ($_POST) {
    echo "<pre>POST data received: ";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<p>No POST data received. Try submitting the form below:</p>";
}
?>
<form method="POST" action="test-form.php">
    <input type="text" name="test_field" value="test value">
    <button type="submit">Submit Test</button>
</form>