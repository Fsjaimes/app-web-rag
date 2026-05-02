<script setup>
    import { Link } from '@inertiajs/vue3'
    
    defineProps({
      message: {
        type: String,
        default: 'No tienes permisos para realizar esta acción.'
      },
      showHomeButton: {
        type: Boolean,
        default: true
      }
    })
    </script>
    
    <template>
      <div class="card" style="width: 100%; margin-top: 200px; border: none;">
        <div class="card-body text-center px-5 py-5">
    
          <!-- Icono -->
          <div class="mb-4">
            <div
              class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
              style="width: 150px; height: 150px;"
            >
              <i
                class="ri-lock-2-fill text-secondary"
                style="font-size: 60px;"
              ></i>
            </div>
          </div>
          <div class="col-md-12">
    
            <h2 class="fw-semibold text-uppercase mb-2">
            Acceso restringido
            </h2>

            <p class="text-muted fs-5 mb-3">
            {{ message }}
            </p>

            <p class="text-muted mb-4">
            Este módulo requiere permisos específicos para su visualización o
            ejecución. Si consideras que deberías tener acceso, solicita la
            habilitación al administrador del sistema.
            </p>
        </div>
      </div>
    </div>
  </template>