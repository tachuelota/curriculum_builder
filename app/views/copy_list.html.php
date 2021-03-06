<?php
  $clean = strip_tags_deep($_GET);
  if (isInstructor()) {
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $("#chkAll").click(function(){
        $(".readingchoice").prop("checked",$("#chkAll").prop("checked"))
    }) 
});
</script>
<div class="readingListLink">
<?php
    $currlistid = decryptCookie($_COOKIE['currentListId']);
    $currauthid = decryptCookie($_COOKIE['currentAuthorId']);
    $sql = $c->prepare("SELECT id, course, linklabel, private FROM lists WHERE id IN (SELECT listid FROM authorlists WHERE authorid = ?) AND id != ? AND credentialconsumerid = ?;");
    $credconsumerID = getCredentialConsumerID();
	$sql->bind_param('iii', $currauthid, $currlistid, $credconsumerID);
	$sql->execute();
	$sql->store_result();
	$sql->bind_result($mylists_id, $mylists_course, $mylists_linklabel, $mylists_private);
	
    if ($sql->num_rows > 0) {
        echo '<form id="mylist" action="copy_list.php" method="get">Your Lists: <select id="mylists" name="listid">';    
        while ($sql->fetch()) {
            if (strlen($mylists_linklabel) <= 0) {
                $mylists_linklabel= 'Untitled List';
            }
            if (strlen($mylists_linklabel) >= 100) {
                $mylists_linklabel = substr($mylists_linklabel,0,99) . "...";
            }
            echo '<option value="' . $mylists_id . '"';
            if (isset($clean['listid'])) {
                if ($clean['listid'] == $mylists_id) {
                    echo ' selected="selected"';
                }
            }
            echo '>' . $mylists_course . ' - ' . $mylists_linklabel;
            if ($mylists_private == 1) {
                echo ' (private)';
            }
            echo '</option>';
        }
        echo '</select> <input type="submit" value="View this list" name="submit" /></form>';
    } else {
        echo '<p>You have no other reading lists.</p>';
    }
    if ($c->more_results()) {
	$c->next_result();
    }
    ?></div><div class="readingListLink">
    <?php
		$sql = $c->prepare("SELECT id, course, linklabel FROM lists WHERE id != ? AND private = 0 AND credentialconsumerid = ?;");
		$sql->bind_param('ii', $currlistid, $credconsumerID);
		$sql->execute();
		$sql->store_result();
		$sql->bind_result($mylists_id, $mylists_course, $mylists_linklabel);
    
    if ($sql->num_rows > 0) {
        echo '<form id="mylist" action="copy_list.php" method="get">All Public Lists: <select id="mylists" name="listid">';    
        while ($sql->fetch()) {
            if (strlen($mylists_linklabel) <= 0) {
                $mylists_linklabel= 'Untitled List';
            }
			
			echo '<option value="' . $mylists_id . '"';
            if (isset($clean['listid'])) {
                if ($clean['listid'] == $mylists_id) {
                    echo ' selected="selected"';
                }
            }
            echo '>' . $mylists_course . ' - ' . $mylists_linklabel . '</option>';
        }
        echo '</select> <input type="submit" value="View this list" name="submit" /></form>';
    } else {
        echo '<p>There are no public reading lists to copy.</p>';
    }
    ?>
    </div>
<?php
    if (isset($clean['submit'])) {
        if (substr_count($clean['submit'],'View') > 0) {
            $targetlistid = $clean['listid'];
            $readingList = getOtherReadingList($c, $targetlistid);
            ?>
            <form action="process_copy.php" method="get" name="otherreadings">
            <div class="readingListLink">
            <strong>Options</strong>
                <p><input type="checkbox" name="include_notes" value="1" checked="checked">Include Notes in the Import Process</p>
                <p><input type="checkbox" name="preserve_order" value="1" checked="checked">Preserve Sort Order</p>
				<p><input type="checkbox" name="include_folders" value="1" checked="checked">Include Folders where applicable</p>
            </div>
            <div class="readingListLink">
                <p><input type="checkbox" id="chkAll" /> <strong>Check/Uncheck All</strong></p>
            </div>
            <div class="readingListLink">
                <?php
                    if (count($readingList) > 0) {
                        foreach ($readingList as $reading) {
                            ?>
                            <p><input type="checkbox" name="<?php echo $reading['id']; ?>" value="1" class="readingchoice"><?php
			    if ($reading['type'] != '3') {
			    echo $reading['title'];
			    } else {
			    echo $reading['instruct'];
			    }
			    if (strlen($reading['notes']) > 0) {
                                echo '<br /><span style="font-size:smaller;"><strong>Notes: </strong>' . $reading['notes'] . '</span>';
                                }
				if (is_integer($reading['folderid'])) {
								echo '<br /><span style="font-size:smaller;"><strong>Folder: </strong>' . getFolderLabel($c,$reading['folderid']) . '</span>';
				}
				?></p>
                            <?php
                        }
                    } else {
                        echo '<p>There are no readings in this list.</p>';
                    }
                ?>
                <p><input type="submit" value="Copy Selected Readings" /></p>
            </div>
            </form>
            <?php
        }
    }
    mysqli_close($c);
  } else {
    echo "<p>You are unauthorized to access this page.</p>";
  }
?>