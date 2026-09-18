<?php /** @var App\Models\Fee $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="code_fee" label="Kode Fee (huruf kecil, tanpa spasi)" />
                <x-input col="4" name="nama_fee" label="Nama Fee" />
                <x-input col="4" type="number" step="0.01" name="value_fee" label="Persen (0–100)" helper="Persen dipotong dari harga produk terjual" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
