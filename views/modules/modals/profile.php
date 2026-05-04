<!-- The Modal -->
<div class="modal" id="myProfile">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded">

      <form method="POST" class="needs-validation" novalidate>

        <!-- Modal Header -->
        <div class="modal-header">
          <h4 class="modal-title text-capitalize">Perfil <?php echo $_SESSION["admin"]->rol_admin ?></h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- Modal body -->
        <div class="modal-body px-4">

          <input type="hidden" name="id_admin" value="<?php echo base64_encode($_SESSION["admin"]->id_admin) ?>">
         
          <div class="form-group mb-3">

            <label for="email_admin">Correo<sup>*</sup></label>

            <input 
            type="email"
            class="form-control rounded"
            id="email_admin"
            name="email_admin"
            value="<?php echo $_SESSION["admin"]->email_admin ?>"
            required
            >

            <div class="valid-feedback">Válido.</div>
            <div class="invalid-feedback">Campo inválido.</div>

          </div>

          <div class="form-group mb-3">

            <label for="password_admin">Contraseña</label>

            <input 
            type="password"
            class="form-control rounded"
            id="password_admin"
            name="password_admin"
            placeholder="*********"
            >

            <div class="valid-feedback">Válido.</div>
            <div class="invalid-feedback">Campo inválido.</div>

          </div>

          <?php if ($_SESSION["admin"]->rol_admin == "superadmin"): ?>

            <div class="form-group mb-3">

              <label for="title_admin">Nombre del Dashboard <sup>*</sup></label>

              <input 
              type="text"
              class="form-control rounded"
              id="title_admin"
              name="title_admin"
              value="<?php echo $admin->title_admin ?>"
              required
              >

              <div class="valid-feedback">Válido.</div>
              <div class="invalid-feedback">Campo inválido.</div>

            </div>

            <div class="form-group mb-3">

              <label for="symbol_admin">Símbolo del Dashboard <sup>*</sup></label>

              <input 
              type="text"
              class="form-control rounded"
              id="symbol_admin"
              name="symbol_admin"
              value="<?php echo htmlspecialchars($admin->symbol_admin) ?>" 
              required
              >

              <div class="valid-feedback">Válido.</div>
              <div class="invalid-feedback">Campo inválido.</div>

            </div>

            <div class="form-group mb-3">

              <label for="font_admin">Tipografía del Dashboard</label>

              <textarea 
              class="form-control rounded"
              id="font_admin"
              name="font_admin"
              value="<?php echo htmlspecialchars($admin->font_admin) ?>"
              ><?php echo htmlspecialchars($admin->font_admin) ?></textarea>

            </div>

            <div class="form-group mb-3">

              <label for="color_admin">Color del Dashboard</label>

              <input 
              type="color"
              class="form-control form-control-color rounded"
              id="color_admin"
              name="color_admin"
              value="<?php echo $admin->color_admin ?>"
              title="Escoge Color"
              >

            </div>

            <div class="form-group mb-3">

              <label for="back_admin">Imagen para el Login</label>

              <input 
              type="text"
              class="form-control rounded"
              id="back_admin"
              name="back_admin"
              value="<?php echo $admin->back_admin ?>"
              >

            </div>

          <?php endif ?>
          
          <!-- Configuración de ChatGPT -->
          <hr class="my-4">
          
          <div class="mb-3">
            <h6 class="mb-3"><i class="bi bi-robot me-2"></i> Configuración de ChatGPT</h6>
            <small class="text-muted d-block mb-3">Configura tus credenciales de OpenAI para usar el asistente de análisis de datos en los reportes.</small>
          </div>
          
          <?php 
          // Obtener configuración actual de ChatGPT
          $chatgptConfig = null;
          if(isset($_SESSION["admin"]->chatgpt_admin) && !empty($_SESSION["admin"]->chatgpt_admin)){
            $chatgptConfig = json_decode($_SESSION["admin"]->chatgpt_admin);
          }
          ?>
          
          <div class="form-group mb-3">
            <label for="chatgpt_token">API Key de OpenAI <sup>*</sup></label>
            <input 
              type="password"
              class="form-control rounded"
              id="chatgpt_token"
              name="chatgpt_token"
              placeholder="sk-proj-..."
              value="<?php echo isset($chatgptConfig->token) ? $chatgptConfig->token : '' ?>"
              autocomplete="off"
            >
            <small class="text-muted">Tu API key de OpenAI. Puedes obtenerla en <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></small>
            <div class="valid-feedback">Válido.</div>
            <div class="invalid-feedback">Campo inválido.</div>
          </div>
          
          <div class="form-group mb-3">
            <label for="chatgpt_org">Organization ID (Opcional)</label>
            <input 
              type="text"
              class="form-control rounded"
              id="chatgpt_org"
              name="chatgpt_org"
              placeholder="org-..."
              value="<?php echo isset($chatgptConfig->org) ? $chatgptConfig->org : '' ?>"
              autocomplete="off"
            >
            <small class="text-muted">Organization ID de OpenAI (opcional, solo si tu cuenta está asociada a una organización)</small>
            <div class="valid-feedback">Válido.</div>
            <div class="invalid-feedback">Campo inválido.</div>
          </div>

        </div>

        <!-- Modal footer -->
        <div class="modal-footer d-flex justify-content-between">
          
          <div><button type="button" class="btn btn-dark rounded" data-bs-dismiss="modal">Cerrar</button></div>
          <div><button type="submit" class="btn btn-default backColor rounded">Guardar</button></div>
          
        </div>

      </form>

    </div>
  </div>
</div>