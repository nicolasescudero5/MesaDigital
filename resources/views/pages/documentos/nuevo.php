<?php
$pageTitle = 'Nuevo Documento';
global $container;
$view = $container->get(\App\Support\View::class);

$isDuplicateWarning = isset($_GET['duplicate_warning']);

$tiposMap = [];
foreach ($tipos as $t) {
    $tiposMap[$t->id] = [
        'id' => $t->id,
        'nombre' => $t->nombre,
        'categoria_id' => $t->categoria_id,
        'categoria_nombre' => $t->categoria_nombre,
    ];
}

$categoriasMap = [];
foreach ($categorias as $c) {
    $resps = [];
    if (!empty($c->responsables)) {
        foreach ($c->responsables as $r) {
            $resps[] = [
                'id' => $r->id,
                'email' => $r->email,
                'usuario_nombre' => $r->usuario_nombre ?? '',
                'sede_id' => $r->sede_id ?? null,
                'sede_nombre' => $r->sede_nombre ?? '',
            ];
        }
    }
    $categoriasMap[$c->id] = [
        'id' => $c->id,
        'nombre' => $c->nombre,
        'responsables' => $resps,
    ];
}
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script src="<?= asset_url('js/qrious.min.js') ?>"></script>
<script src="<?= asset_url('js/qrcode.min.js') ?>"></script>

<script>
function nuevoDocumentoForm() {
    return {
        filesCount: 0,
        fileNames: [],
        sedeId: <?= json_encode((string)old('sede_id', $user->sede_id ?? '')) ?>,
        tipoId: <?= json_encode((string)old('tipo_documento_id', '')) ?>,
        categoriaId: <?= json_encode((string)old('categoria_id', '')) ?>,
        responsableEmail: <?= json_encode((string)old('responsable_email', '')) ?>,
        tipos: <?= json_encode($tiposMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        categorias: <?= json_encode($categoriasMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        qrToken: "",
        qrUrl: "",
        qrStatus: "loading",
        qrFiles: [],
        tempToken: <?= json_encode((string)old('temp_token', '')) ?>,
        pollInterval: null,
        init() {
            if (this.tipoId) {
                this.recalculateDerivation();
            }
            if (this.tempToken) {
                this.restoreQrSession(this.tempToken);
            } else {
                this.generateQrSession();
            }
        },
        restoreQrSession(token) {
            this.qrToken = token;
            this.qrUrl = "<?= app_url('/cargar?token=', true) ?>" + token;
            this.qrStatus = "loading";
            fetch("<?= app_url('/api/upload-session/status') ?>?token=" + token)
            .then(r => r.json())
            .then(res => {
                if (res.status === "completed" || res.status === "pending") {
                    this.qrStatus = res.status;
                    this.qrFiles = res.files || [];
                    this.filesCount = this.qrFiles.length;
                    this.fileNames = this.qrFiles.map(f => f.original_name + " (Enviado desde Celular / QR)");
                    this.$nextTick(() => {
                        this.renderQrCode();
                    });
                    this.startPolling();
                } else {
                    this.tempToken = "";
                    this.generateQrSession();
                }
            })
            .catch(() => {
                this.generateQrSession();
            });
        },
        generateQrSession() {
            this.qrStatus = "loading";
            const csrfInput = document.querySelector('input[name="_csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : "";
            fetch("<?= app_url('/api/upload-session/create') ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.qrToken = data.token;
                    this.qrUrl = data.upload_url;
                    this.qrStatus = "pending";
                    this.$nextTick(() => {
                        this.renderQrCode();
                    });
                    this.startPolling();
                } else {
                    console.error("Error al generar sesión QR:", data);
                    this.qrStatus = "idle";
                }
            })
            .catch(err => {
                console.error("Error al conectar con API QR:", err);
                this.qrStatus = "idle";
            });
        },
        renderQrCode() {
            const container = document.getElementById("inline_qrcode_container");
            if (!container) return;
            container.innerHTML = "";
            if (!this.qrUrl) return;

            try {
                if (typeof QRious !== "undefined") {
                    const canvas = document.createElement("canvas");
                    container.appendChild(canvas);
                    new QRious({
                        element: canvas,
                        value: this.qrUrl,
                        size: 160,
                        level: 'H'
                    });
                } else if (typeof QRCode !== "undefined") {
                    new QRCode(container, {
                        text: this.qrUrl,
                        width: 160,
                        height: 160
                    });
                } else {
                    const img = document.createElement("img");
                    img.src = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=" + encodeURIComponent(this.qrUrl);
                    img.alt = "Código QR";
                    img.className = "w-[160px] h-[160px] rounded-lg shadow-sm";
                    container.appendChild(img);
                }
            } catch (e) {
                console.error("Error al renderizar código QR:", e);
                const img = document.createElement("img");
                img.src = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=" + encodeURIComponent(this.qrUrl);
                img.alt = "Código QR";
                img.className = "w-[160px] h-[160px] rounded-lg shadow-sm";
                container.appendChild(img);
            }
        },
        startPolling() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(() => {
                if (!this.qrToken || this.qrStatus === "expired") return;
                fetch("<?= app_url('/api/upload-session/status') ?>?token=" + this.qrToken)
                .then(r => r.json())
                .then(res => {
                    if (res.status === "completed") {
                        this.qrStatus = "completed";
                        this.qrFiles = res.files || [];
                        this.tempToken = this.qrToken;
                        this.filesCount = this.qrFiles.length;
                        this.fileNames = this.qrFiles.map(f => f.original_name + " (Enviado desde Celular / QR)");
                    } else if (res.status === "expired") {
                        this.qrStatus = "expired";
                        clearInterval(this.pollInterval);
                    }
                })
                .catch(() => {});
            }, 1000);
        },
        recalculateDerivation() {
            const t = this.tipos[this.tipoId];
            if (t && t.categoria_id) {
                this.categoriaId = String(t.categoria_id);
                const resps = this.getCurrentResponsables();
                if (resps.length === 1) {
                    this.responsableEmail = resps[0].email;
                } else {
                    this.responsableEmail = "";
                }
            } else {
                this.categoriaId = "";
                this.responsableEmail = "";
            }
        },
        getCurrentResponsables() {
            if (!this.categoriaId || !this.categorias[this.categoriaId]) return [];
            const all = this.categorias[this.categoriaId].responsables || [];
            if (!this.sedeId) return all;
            const sedeSpecific = all.filter(r => String(r.sede_id) === String(this.sedeId));
            if (sedeSpecific.length > 0) return sedeSpecific;
            return all.filter(r => !r.sede_id);
        },
        getCurrentCategoriaNombre() {
            if (!this.categoriaId || !this.categorias[this.categoriaId]) return "";
            return this.categorias[this.categoriaId].nombre;
        },
        handleFiles(event) {
            const files = event.target.files;
            this.filesCount = files.length;
            this.fileNames = Array.from(files).map(f => f.name + " (" + (f.size / 1024 / 1024).toFixed(2) + " MB)");
        }
    };
}
</script>

<?= $view->partial('page-header', [
    'title' => 'Registrar Nuevo Documento',
    'subtitle' => 'Carga de correspondencia con derivación automática según la sede y tipo de documento',
    'breadcrumbs' => [
        ['label' => 'Bandeja', 'url' => '/documentos'],
        ['label' => 'Nuevo Registro']
    ]
]) ?>

<div class="max-w-2xl mx-auto" x-data="nuevoDocumentoForm()">

    <!-- Alerta de Posible Duplicado (§ 4.5) -->
    <?php if ($isDuplicateWarning): ?>
        <div class="mb-6 p-4 rounded-xl border border-warning-300 bg-warning-50 text-warning-900 dark:bg-warning-950/60 dark:text-warning-200 dark:border-warning-800 flex items-start gap-3 shadow-xs">
            <span class="text-warning-600 shrink-0 mt-0.5"><?= icon('alert-triangle', 'w-5 h-5') ?></span>
            <div>
                <h4 class="font-bold text-sm">Alerta de posible documento duplicado</h4>
                <p class="text-xs mt-1">Ya existe un documento cargado en las últimas 24 horas para este remitente y sede. Para confirmar la carga, marcá la casilla al pie del formulario.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="card p-6 md:p-8 shadow-card">
        <form action="<?= app_url('/documentos') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="temp_token" :value="tempToken">

            <!-- ZONA DE ADJUNTO / CARGA QR EN VIVO (§ 7.5 - Control Central) -->
            <div>
                <label class="form-label flex items-center justify-between mb-2">
                    <span>Foto o Archivo del Documento <span class="text-danger-500">*</span></span>
                    <span x-show="tempToken" class="text-xs font-bold text-success-600 flex items-center gap-1">
                        <?= icon('check-circle', 'w-4 h-4 text-success-600') ?> Archivo recibido vía QR
                    </span>
                </label>

                <div class="border-2 border-dashed border-brand-300 dark:border-brand-700 hover:border-brand-500 rounded-2xl p-6 text-center bg-brand-50/20 dark:bg-brand-950/20 transition-all">
                    
                    <!-- BLOQUE CENTRAL QR Y ESTADO -->
                    <div class="flex flex-col items-center justify-center mb-4">
                        
                        <!-- Estado 1: Loading -->
                        <template x-if="qrStatus === 'loading'">
                            <div class="py-6 flex flex-col items-center justify-center gap-2">
                                <span class="animate-spin text-brand-600"><?= icon('loader', 'w-8 h-8') ?></span>
                                <span class="text-xs text-ink-500 font-medium">Generando código QR...</span>
                            </div>
                        </template>

                        <!-- Estado 2: Pending o Completed (QR desplegado en vivo de forma continua) -->
                        <template x-if="qrStatus === 'pending' || qrStatus === 'completed'">
                            <div class="flex flex-col items-center gap-3">
                                
                                <!-- Badge de Archivos Recibidos -->
                                <template x-if="qrStatus === 'completed'">
                                    <div class="py-2 px-4 bg-success-50 dark:bg-success-950/50 border border-success-200 dark:border-success-800 rounded-xl flex items-center gap-2 text-xs font-bold text-success-800 dark:text-success-200 shadow-xs">
                                        <?= icon('check-circle', 'w-4 h-4 text-success-600') ?>
                                        <span x-text="filesCount + ' archivo(s) recibido(s) desde el celular'"></span>
                                    </div>
                                </template>

                                <!-- Código QR -->
                                <div class="bg-white p-3 rounded-2xl border border-brand-200 dark:border-brand-800 shadow-sm hover:shadow-md transition-shadow">
                                    <div id="inline_qrcode_container" class="flex justify-center items-center"></div>
                                </div>

                                <!-- Mensaje indicativo -->
                                <div class="flex items-center gap-2 text-xs font-bold text-brand-700 dark:text-brand-300 bg-brand-100/70 dark:bg-brand-900/40 px-3.5 py-1.5 rounded-full border border-brand-200 dark:border-brand-700 shadow-xs">
                                    <span class="animate-ping w-2 h-2 rounded-full bg-brand-500 shrink-0"></span>
                                    <span x-text="qrStatus === 'completed' ? 'Podés seguir escaneando o enviando más fotos desde el celular' : 'Escaneá con tu celular para subir fotos o PDFs'"></span>
                                </div>

                                <div class="text-[11px] text-ink-400">
                                    ¿Estás probando en la misma PC? <a :href="qrUrl" target="_blank" class="text-brand-600 underline font-bold hover:text-brand-700">Abrir enlace en otra pestaña</a>
                                </div>
                            </div>
                        </template>

                        <!-- Estado 4: Expirado / Inactivo -->
                        <template x-if="qrStatus === 'expired' || qrStatus === 'idle'">
                            <div class="py-4 flex flex-col items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-warning-100 text-warning-600 flex items-center justify-center">
                                    <?= icon('alert-triangle', 'w-5 h-5 text-warning-600') ?>
                                </div>
                                <span class="text-xs text-ink-500">Sesión QR inactiva o expirada</span>
                                <button type="button" @click="generateQrSession" class="btn-neutral text-xs px-3 py-1.5 font-bold">Generar Nuevo QR</button>
                            </div>
                        </template>

                    </div>

                    <!-- OPCIÓN SECUNDARIA: SELECCIONAR DESDE LA PC -->
                    <div class="pt-3 border-t border-ink-200/60 dark:border-ink-800 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <label class="btn-neutral cursor-pointer text-xs font-semibold inline-flex items-center gap-2 shadow-xs bg-white dark:bg-ink-800 border border-ink-300 dark:border-ink-700 hover:bg-ink-100">
                            <?= icon('upload-cloud', 'w-4 h-4 text-ink-500') ?>
                            <span>Seleccionar desde la PC (PDF, JPG, PNG)</span>
                            <input type="file" name="adjuntos[]" accept="image/jpeg,image/png,image/webp,application/pdf" class="hidden" @change="handleFiles" multiple>
                        </label>
                    </div>

                    <p class="text-[11px] text-ink-400 mt-2">
                        Formatos permitidos: JPG, PNG, WebP o PDF. Máximo 10 MB por archivo.
                    </p>

                    <!-- Lista de archivos seleccionados -->
                    <template x-if="filesCount > 0">
                        <div class="mt-4 p-3 bg-white dark:bg-ink-800 rounded-xl border border-ink-200 dark:border-ink-700 text-left shadow-xs">
                            <div class="text-xs font-bold text-ink-800 dark:text-white mb-1 flex items-center gap-1.5">
                                <span class="text-success-600"><?= icon('check-circle', 'w-4 h-4 text-success-600') ?></span>
                                <span x-text="filesCount + ' archivo(s) listo(s) para registrar:'"></span>
                            </div>
                            <ul class="text-xs text-ink-600 dark:text-ink-300 list-disc list-inside space-y-0.5">
                                <template x-for="name in fileNames" :key="name">
                                    <li x-text="name"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Sede -->
                <div>
                    <label for="sede_id" class="form-label">
                        Sede de Recepción <span class="text-danger-500">*</span>
                    </label>
                    <?php if ($user->isRecepcion()): ?>
                        <input type="hidden" name="sede_id" value="<?= $user->sede_id ?>">
                        <input type="text" disabled class="form-input bg-ink-100 dark:bg-ink-800 font-bold text-ink-900 dark:text-white" value="<?= e($user->sede_nombre) ?>">
                    <?php else: ?>
                        <select id="sede_id" name="sede_id" x-model="sedeId" @change="recalculateDerivation" required class="form-select font-medium">
                            <option value="">Seleccionar sede...</option>
                            <?php foreach ($sedes as $s): ?>
                                <option value="<?= $s->id ?>" <?= old('sede_id') == $s->id ? 'selected' : '' ?>>
                                    <?= e($s->nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Tipo de Documento -->
                <div>
                    <label for="tipo_documento_id" class="form-label">
                        Tipo de Documento <span class="text-danger-500">*</span>
                    </label>
                    <select id="tipo_documento_id" name="tipo_documento_id" x-model="tipoId" @change="recalculateDerivation" required class="form-select font-bold text-ink-900 dark:text-white">
                        <option value="">Seleccionar tipo...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t->id ?>">
                                <?= e($t->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Categoría & Responsables Asignados Automáticamente -->
                <div class="sm:col-span-2">
                    <input type="hidden" name="categoria_id" :value="categoriaId">
                    
                    <div class="p-4 rounded-xl border border-ink-200 dark:border-ink-700 bg-ink-50/70 dark:bg-ink-900/60 space-y-3">
                        
                        <!-- Estado de la Categoría vinculada -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-ink-500">Categoría Derivada Automáticamente</span>
                                <div class="mt-0.5">
                                    <template x-if="getCurrentCategoriaNombre()">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-brand-500 text-white font-bold text-xs shadow-xs">
                                                <?= icon('tag', 'w-3.5 h-3.5 text-white') ?>
                                                <span x-text="getCurrentCategoriaNombre()"></span>
                                            </span>
                                        </div>
                                    </template>
                                    <template x-if="!getCurrentCategoriaNombre()">
                                        <span class="text-xs text-ink-400 italic">Seleccioná un tipo de documento para asignar el área</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Caso 1: Un solo responsable asignado -->
                            <template x-if="getCurrentResponsables().length === 1">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-success-50 text-success-800 dark:bg-success-950/40 dark:text-success-200 border border-success-200 dark:border-success-800 text-xs font-semibold">
                                    <?= icon('check-circle', 'w-4 h-4 text-success-600') ?>
                                    <span>Responsable asignado: <strong x-text="getCurrentResponsables()[0].usuario_nombre || getCurrentResponsables()[0].email"></strong></span>
                                    <span class="text-[10px] opacity-75 font-normal" x-show="getCurrentResponsables()[0].sede_nombre" x-text="'(' + getCurrentResponsables()[0].sede_nombre + ')'"></span>
                                    <input type="hidden" name="responsable_email" :value="getCurrentResponsables()[0].email">
                                </div>
                            </template>
                        </div>

                        <!-- Caso 2: Múltiples responsables (Permitir a la recepcionista elegir o notificar a todos) -->
                        <template x-if="getCurrentResponsables().length > 1">
                            <div class="pt-3 border-t border-ink-200 dark:border-ink-800">
                                <label class="form-label text-xs font-bold text-ink-800 dark:text-ink-200 flex items-center justify-between">
                                    <span>Responsable Destinatario</span>
                                    <span class="text-[10px] text-brand-600 font-semibold" x-text="getCurrentResponsables().length + ' responsables disponibles'"></span>
                                </label>
                                <select name="responsable_email" x-model="responsableEmail" class="form-select text-xs font-medium">
                                    <option value="">(Notificar a todos los responsables de esta categoría)</option>
                                    <template x-for="resp in getCurrentResponsables()" :key="resp.email">
                                        <option :value="resp.email" x-text="(resp.usuario_nombre ? (resp.usuario_nombre + ' — ' + resp.email) : resp.email) + (resp.sede_nombre ? ' [' + resp.sede_nombre + ']' : '')"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <!-- Caso 3: Categoría sin responsables -->
                        <template x-if="categoriaId && getCurrentResponsables().length === 0">
                            <div class="p-2.5 rounded-lg bg-warning-50 dark:bg-warning-950/30 text-warning-800 dark:text-warning-200 border border-warning-200 text-xs flex items-center gap-2">
                                <?= icon('alert-triangle', 'w-4 h-4 text-warning-600') ?>
                                <span>Esta categoría no posee responsables activos asignados. Se enviará una alerta automática al Administrador.</span>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Remitente -->
                <div>
                    <label for="remitente" class="form-label">
                        Remitente / Empresa / Organismo <span class="text-danger-500">*</span>
                    </label>
                    <input type="text" id="remitente" name="remitente" required value="<?= e(old('remitente')) ?>" 
                           placeholder="Ej: Juzgado Civil N° 4, Correo Argentino..." class="form-input">
                </div>

                <!-- Carácter del Remitente -->
                <div>
                    <label for="caracter_remitente_id" class="form-label">
                        Carácter del Remitente
                    </label>
                    <select id="caracter_remitente_id" name="caracter_remitente_id" class="form-select font-medium">
                        <option value="">Opcional / No especificado</option>
                        <?php foreach ($caracteres as $car): ?>
                            <option value="<?= $car->id ?>" <?= old('caracter_remitente_id') == $car->id ? 'selected' : '' ?>>
                                <?= e($car->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Asunto -->
                <div class="sm:col-span-2">
                    <label for="asunto" class="form-label">
                        Asunto / Título Resumido <span class="text-danger-500">*</span>
                    </label>
                    <input type="text" id="asunto" name="asunto" required value="<?= e(old('asunto')) ?>" 
                           placeholder="Ej: Notificación de cédula judicial sobre expediente..." class="form-input font-medium">
                </div>

                <!-- Descripción detallada -->
                <div class="sm:col-span-2">
                    <label for="descripcion" class="form-label">
                        Detalle / Observaciones <span class="text-danger-500">*</span>
                    </label>
                    <textarea id="descripcion" name="descripcion" required class="form-textarea" rows="3" 
                              placeholder="Indicar observaciones relevantes, número de guía/expediente, persona de contacto..."><?= e(old('descripcion')) ?></textarea>
                </div>

                <!-- Fecha de Recepción -->
                <div>
                    <label for="fecha_recepcion" class="form-label">
                        Fecha de Recepción <span class="text-danger-500">*</span>
                    </label>
                    <input type="date" id="fecha_recepcion" name="fecha_recepcion" required 
                           value="<?= e(old('fecha_recepcion', date('Y-m-d'))) ?>" class="form-input font-medium">
                </div>

                <!-- Plazo Legal (Opcional) -->
                <div>
                    <label for="plazo_legal" class="form-label">
                        Plazo Legal / Vencimiento
                    </label>
                    <input type="date" id="plazo_legal" name="plazo_legal" 
                           value="<?= e(old('plazo_legal')) ?>" class="form-input font-medium">
                    <p class="text-xs text-ink-500 dark:text-ink-400 mt-1">Si se completa, disparará alertas a 3 y 1 día antes.</p>
                </div>

            </div>

            <!-- Casilla de confirmación en caso de duplicado -->
            <?php if ($isDuplicateWarning): ?>
                <div class="pt-4 border-t border-ink-200 dark:border-ink-800">
                    <label class="flex items-center gap-2 text-xs font-bold text-ink-900 dark:text-white cursor-pointer">
                        <input type="checkbox" name="confirm_duplicate" value="1" required class="rounded border-ink-300 text-brand-600">
                        <span>Confirmar que deseo registrar este documento a pesar de la advertencia de duplicado.</span>
                    </label>
                </div>
            <?php endif; ?>

            <!-- Botones de Guardado -->
            <div class="pt-6 border-t border-ink-200 dark:border-ink-800 flex items-center justify-end gap-3">
                <a href="<?= app_url('/documentos') ?>" class="btn-ghost font-semibold">Cancelar</a>
                <button type="submit" class="btn-primary px-6 inline-flex items-center gap-2 shadow-xs font-bold text-white">
                    <?= icon('check', 'w-4 h-4 text-white') ?>
                    <span>Registrar y Notificar</span>
                </button>
            </div>
        </form>
    </div>
</div>
