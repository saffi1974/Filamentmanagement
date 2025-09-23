<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$h = ['name'=>'', 'kommentar'=>'']; // Standardwerte

if($id > 0){
    $res = $conn->query("SELECT * FROM materialien WHERE id=$id");
    if($res) $h = $res->fetch_assoc();
}

if(isset($_POST['submit'])){
    $name = $_POST['name'];
    $kommentar = $_POST['kommentar'];

    if($id > 0){
        // Update
        $stmt = $conn->prepare("UPDATE materialien SET name=?, kommentar=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $kommentar, $id);
    } else {
        // Neu anlegen
        $stmt = $conn->prepare("INSERT INTO materialien (name, kommentar) VALUES (?,?)");
        $stmt->bind_param("sis", $name, $kommentar);
    }
    $stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Material erfolgreich bearbeitet!</span>
	</div>';
	header("Location: index.php?site=material");
    exit;
}
?>
<section class="form-section">
  <h2><?php echo $id > 0 ? "Material bearbeiten" : "Neues Material anlegen"; ?></h2>
  <form method="post">
    <div class="form-group">
      <label for="name">Materialname</label>
      <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($h['name']); ?>" required>
    </div>

    <div class="form-group">
      <label for="kommentar">Kommentar</label>
      <textarea rows="3" cols="40" id="kommentar" name="kommentar"><?php echo htmlspecialchars($h['kommentar']); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
    <button type="submit" name="submit" class="btn-submit"><?php echo $id > 0 ? "Speichern" : "Eintragen"; ?></button>
	<a href="index.php?site=material" class="btn-submit" style="text-decoration:none; display:inline-block;">Abbrechen</a>
    </div>
	
  </form>
</section>
