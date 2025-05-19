<?php
// vistas/parciales/mislistas_partial_view.php
// La variable $listas es pasada por el ProfileController->loadSection()
?>
<button onclick="const modal = document.getElementById('crearListaModal'); if(modal) modal.classList.remove('hidden');"
    class="bg-orange-500 text-white px-4 py-2 rounded-full mb-6 hover:bg-orange-600 transition">
    Crear Nueva Lista
</button>

<div id="crearListaModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-lg w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4">Crear Nueva Lista</h2>
        <form id="crearListaForm"> <input type="text" name="nombreLista" placeholder="Nombre de la lista"
                class="w-full p-2 mb-4 border border-gray-300 rounded" required>
            <textarea name="descripcion" placeholder="Descripción de la lista"
                class="w-full p-2 mb-4 border border-gray-300 rounded"></textarea>
            <label class="inline-flex items-center mb-4">
                <input type="checkbox" name="publica" class="form-checkbox h-5 w-5 text-orange-600">
                <span class="ml-2 text-gray-700">¿Lista pública?</span>
            </label>
            <div class="flex justify-end gap-2">
                <button type="button"
                    onclick="const modal = document.getElementById('crearListaModal'); if(modal) modal.classList.add('hidden');"
                    class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
                <button type="submit"
                    class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">Crear</button>
            </div>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 mt-6 w-full" id="listasContainer">
    <?php if (!empty($listas)): ?>
        <?php foreach ($listas as $lista): ?>
            <div class="h-[200px] bg-white rounded-[10px] p-4 shadow-md flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold mb-2 text-orange-600 truncate" title="<?php echo htmlspecialchars($lista['NombreLista']); ?>"><?php echo htmlspecialchars($lista['NombreLista']); ?></h2>
                    <p class="text-gray-600 text-sm break-words"><?php echo nl2br(htmlspecialchars($lista['Descripcion'])); ?></p>
                </div>
                <p class="text-xs mt-2 text-gray-500 self-end"><?php echo ($lista['Publica'] ? 'Pública' : 'Privada'); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-gray-600 col-span-full text-center py-10">No tienes listas creadas aún. ¡Crea una!</p>
    <?php endif; ?>
</div>