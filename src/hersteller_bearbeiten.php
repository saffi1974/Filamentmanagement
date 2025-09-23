<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$h = ['hr_name'=>'', 'hr_leerspule'=>0, 'hr_kommentar'=>'']; // Standardwerte

if($id > 0){
    $res = $conn->query("SELECT * FROM hersteller WHERE id=$id");
    if($res) $h = $res->fetch_assoc();
}

if(isset($_POST['submit'])){
    $name = $_POST['hr_name'];
    $gewicht = intval($_POST['hr_leerspule']);
    $kommentar = $_POST['hr_kommentar'];

    if($id > 0){
        // Update
        $stmt = $conn->prepare("UPDATE hersteller SET hr_name=?, hr_leerspule=?, hr_kommentar=? WHERE id=?");
        $stmt->bind_param("sisi", $name, $gewicht, $kommentar, $id);
    } else {
        // Neu anlegen
        $stmt = $conn->prepare("INSERT INTO hersteller (hr_name, hr_leerspule, hr_kommentar) VALUES (?,?,?)");
        $stmt->bind_param("sis", $name, $gewicht, $kommentar);
    }
    $stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Hersteller erfolgreich geändert!</span>
	</div>';
    header("Location: index.php?site=hersteller");
    exit;
}
?>
<section class="form-section">
  <h2><?php echo $id > 0 ? "Hersteller bearbeiten" : "Neuen Hersteller anlegen"; ?></h2>
  <form method="post">
    <div class="form-group">
      <label for="hr_name">Herstellername</label>
      <input type="text" id="hr_name" name="hr_name" value="<?php echo htmlspecialchars($h['hr_name']); ?>" required>
    </div>

    <div class="form-group">
      <label for="hr_gewicht">Gewicht der Leerspule (g)</label>
      <input type="number" id="hr_gewicht" name="hr_leerspule" value="<?php echo htmlspecialchars($h['hr_leerspule']); ?>">
    </div>

    <div class="form-group">
      <label for="hr_kommentar">Kommentar</label>
      <textarea rows="3" cols="40" id="hr_kommentar" name="hr_kommentar"><?php echo htmlspecialchars($h['hr_kommentar']); ?></textarea>
    </div>

    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
    <button type="submit" name="submit" class="btn-submit"><?php echo $id > 0 ? "Speichern" : "Eintragen"; ?></button>
	<a href="index.php?site=hersteller" class="btn-submit" style="text-decoration:none; display:inline-block;">Abbrechen</a>
    </div>
	
  </form>
</section>
