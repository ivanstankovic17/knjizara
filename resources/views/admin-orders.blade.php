<x-shop title="Porudžbine" script="resources/js/admin-orders.js">
    <form id="filter" class="filteri">
        <label>Status
            <select name="status">
                <option value="">svi</option>
                <option value="pending" selected>na čekanju</option>
                <option value="paid">plaćena</option>
                <option value="shipped">poslata</option>
                <option value="cancelled">otkazana</option>
            </select>
        </label>
    </form>

    <p id="status" class="status" role="status" aria-live="polite"></p>
    <ul id="porudzbine" class="porudzbine"></ul>
</x-shop>
