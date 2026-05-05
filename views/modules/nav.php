<nav class="navbar navbar-expand-lg">
					
	<div>
		<button class="btn btn-light" id="menu-toggle">
			<i class="bi bi-list"></i>
		</button>
	</div>

	<div class="d-flex align-items-center">

		<div class="d-flex align-items-center me-3">

			<?php if ($_SESSION["admin"]->id_office_admin > 0): ?>
		
				<?php if (!isset($_SESSION["admin"]->phone_office)): ?>

					<?php if (isset($_GET["offices"])): ?>

						<a href="#myOffices" data-bs-toggle="modal" class="badge backColor py-2 px-3"><?php echo urldecode(explode("_",$_GET["offices"])[1]) ?></a>

					<?php else: ?>

						<a href="#myOffices" data-bs-toggle="modal" class="badge backColor py-2 px-3"><?php echo urldecode($_SESSION["admin"]->title_office) ?></a>

					<?php endif ?>

				<?php else: ?>

					<span class="badge backColor py-2 px-3"><?php echo urldecode($_SESSION["admin"]->title_office) ?></span>

				<?php endif ?>

			<?php else: ?>

				<?php if (isset($_GET["offices"])): ?>

					<a href="#myOffices" data-bs-toggle="modal" class="badge backColor py-2 px-3"><?php echo urldecode(explode("_",$_GET["offices"])[1]) ?></a>

				<?php else: ?>

					<a href="#myOffices" data-bs-toggle="modal" class="badge backColor py-2 px-3">Multi-Sucursal</a>
					
				<?php endif ?>


			<?php endif ?>

			<a href="#myProfile" class="ms-3 profile-link" data-bs-toggle="modal">
				<i class="bi bi-person-circle"></i>
				<span class="ms-1 fw-bold"><?php echo urldecode($_SESSION["admin"]->name_admin) ?></span>
			</a>

		</div>

		<div class="border-start ps-3 py-1">
			
			<a href="/logout" class="logout-link">				
				<i class="bi bi-box-arrow-right"></i>
			</a>

		</div>

	</div>

</nav>

<style>
.profile-link {
	color: var(--dark-green) !important;
	text-decoration: none !important;
	font-size: 0.9rem;
	display: flex;
	align-items: center;
	padding: 8px 12px;
	border-radius: 12px;
	transition: var(--transition);
}
.profile-link:hover {
	background: var(--sidebar-accent);
}
.logout-link {
	width: 40px;
	height: 40px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 12px;
	background: rgba(229, 57, 53, 0.08);
	color: #e53935 !important;
	transition: var(--transition);
}
.logout-link:hover {
	background: rgba(229, 57, 53, 0.15);
	transform: translateY(-2px);
}
</style>