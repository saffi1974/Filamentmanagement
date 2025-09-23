<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $stmt = $conn->prepare("REPLACE INTO firmendaten (id, firmenname, strasse, plz, ort, telefon, email, name_bank, bic, konto_nummer, logo)
                            VALUES (1,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssss",
        $_POST['firmenname'],
        $_POST['strasse'],
        $_POST['plz'],
        $_POST['ort'],
        $_POST['telefon'],
        $_POST['email'],
		$_POST['name_bank'],
		$_POST['bic'],
		$_POST['konto_nummer'],
        $_POST['logo']
    );
    $stmt->execute();
    $_SESSION['success'] = "Firmendaten gespeichert.";
    header("Location: index.php?site=firmendaten");
    exit;
}

// Aktuelle Daten laden
$res = $conn->query("SELECT * FROM firmendaten WHERE id=1");
$data = $res->fetch_assoc();
?>
<section class="card">
    <div class="card-header">
		<h2>Firmendaten</h2>
    </div>
    
    <form method="post" class="form">
		<div class="form-group" style="width:40%">
			<label>Firmenname</label>
			<input type="text" name="firmenname" value="<?= htmlspecialchars($data['firmenname'] ?? '') ?>">
		</div>
		<div class="form-group" style="width:40%">        
			<label>Straße</label>
			<input type="text" name="strasse" value="<?= htmlspecialchars($data['strasse'] ?? '') ?>">
		</div>

		<div class="form-group" style="width:40%">
			<label>PLZ</label>
			<input type="text" name="plz" value="<?= htmlspecialchars($data['plz'] ?? '') ?>">
		</div>

		<div class="form-group" style="width:40%">
			<label>Ort</label>
			<input type="text" name="ort" value="<?= htmlspecialchars($data['ort'] ?? '') ?>">
		</div>

		<div class="form-group" style="width:40%">
			<label>Telefon</label>
			<input type="text" name="telefon" value="<?= htmlspecialchars($data['telefon'] ?? '') ?>">
		</div>

		<div class="form-group" style="width:40%">
			<label>E-Mail</label>
			<input type="text" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>">
		</div>

		<div class="form-group" style="width:40%">
			<label>Name der Bank</label>
			<input type="text" name="name_bank" value="<?= htmlspecialchars($data['name_bank'] ?? '') ?>">
			
			<label>BIC der Bank</label>
			<input type="text" name="bic" value="<?= htmlspecialchars($data['bic'] ?? '') ?>">
			
			<label>Kontonummer</label>
			<input type="text" name="konto_nummer" value="<?= htmlspecialchars($data['konto_nummer'] ?? '') ?>">


		</div>

		<div class="form-group" style="width:40%">
			<label>Logo (Pfad)</label>
			<input type="text" name="logo" value="<?= htmlspecialchars($data['logo'] ?? '') ?>">
		</div>

        <button type="submit" name="save" class="btn-primary">💾 Speichern</button>
    </form>
</section>
