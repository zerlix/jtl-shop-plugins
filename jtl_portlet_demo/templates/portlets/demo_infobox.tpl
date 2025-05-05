{* Einfaches Debug-Template *}
<div class="card mt-3 mb-3" style="border: 2px solid red;">
    <div class="card-header">
        <h5>DEBUG: {$data.title}</h5>
    </div>
    <div class="card-body">
        <p>Content: {$data.content}</p>
        
        {if isset($data.product) && $data.product !== null}
            <p>Produkt vorhanden: Ja</p>
            <p>Name: {$data.productName}</p>
            <p>Nummer: {$data.productNumber}</p>
        {else}
            <p>Produkt vorhanden: Nein</p>
        {/if}
    </div>
</div>