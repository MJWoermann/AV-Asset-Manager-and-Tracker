@php
    $initial = [
        'name' => old('name', $layout->name),
        'paper_preset' => old('paper_preset', $layout->paper_preset),
        'width_mm' => (float) old('width_mm', $layout->width_mm),
        'height_mm' => (float) old('height_mm', $layout->height_mm),
        'orientation' => old('orientation', $layout->orientation ?: 'portrait'),
        'elements' => old('elements')
            ? (is_string(old('elements')) ? json_decode(old('elements'), true) : old('elements'))
            : ($layout->elements ?? []),
        'logo_url' => $layout->logoUrl(),
    ];
    if (! is_array($initial['elements'])) {
        $initial['elements'] = [];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $layout->exists ? 'Edit shelf tag layout' : 'New shelf tag layout' }}</h2>
            <a href="{{ route('shelf-tags.layouts.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Back to layouts</a>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto px-4"
        x-data="shelfTagDesigner(@js($initial), @js($presets), @js($fieldOptions))"
    >
        @if (session('status'))
            <p class="mb-3 text-sm text-brand">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-3 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ $layout->exists ? route('shelf-tags.layouts.update', $layout) : route('shelf-tags.layouts.store') }}"
            class="space-y-4"
            @submit="syncElementsJson()"
        >
            @csrf
            @if($layout->exists) @method('PUT') @endif
            <input type="hidden" name="elements" x-ref="elementsInput" :value="elementsJson">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-3 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" x-model="name" required />
                    </div>
                    <div>
                        <x-input-label for="paper_preset" value="Paper preset" />
                        <select
                            id="paper_preset"
                            name="paper_preset"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="paperPreset"
                            @change="onPresetChange()"
                        >
                            <template x-for="(preset, key) in presets" :key="key">
                                <option :value="key" x-text="preset.label" :selected="paperPreset === key"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver" x-text="presetNotes"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-input-label for="width_mm" value="Width (mm)" />
                            <x-text-input id="width_mm" name="width_mm" type="number" step="0.1" min="10" class="mt-1 block w-full" x-model.number="widthMm" required />
                        </div>
                        <div>
                            <x-input-label for="height_mm" value="Height / length (mm)" />
                            <x-text-input id="height_mm" name="height_mm" type="number" step="0.1" min="10" class="mt-1 block w-full" x-model.number="heightMm" required />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="orientation" value="Orientation" />
                        <select id="orientation" name="orientation" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" x-model="orientation">
                            <option value="portrait">Portrait</option>
                            <option value="landscape">Landscape</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="logo" value="Logo (jpg/png, max 2MB)" />
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm">
                        <template x-if="logoUrl">
                            <label class="mt-2 inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded text-brand">
                                Remove current logo
                            </label>
                        </template>
                    </div>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        In the Brother driver, pick the matching DK size, Actual size, and Cut every for continuous media.
                    </p>
                </div>

                <div class="lg:col-span-6 space-y-3">
                    <div class="bg-white dark:bg-brand-slate border p-3">
                        <div class="flex flex-wrap gap-2 mb-3 text-sm">
                            <button type="button" class="px-2 py-1 border" @click="addElement('text')">Text</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('field')">Field</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('barcode')">Barcode</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('qr')">QR</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('rect')">Rect</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('line')">Line</button>
                            <button type="button" class="px-2 py-1 border" @click="addElement('logo')">Logo</button>
                        </div>
                        <div class="overflow-auto border border-dashed border-brand-charcoal/40 bg-brand-silver/20 p-4">
                            <div
                                class="relative bg-white shadow-sm mx-auto"
                                :style="canvasStyle"
                                @pointerdown.self="selectedId = null"
                            >
                                <template x-for="el in sortedElements" :key="el.id">
                                    <div
                                        class="absolute border border-transparent select-none"
                                        :data-el-id="el.id"
                                        :class="selectedId === el.id ? 'ring-2 ring-brand border-brand' : 'hover:border-brand-charcoal/40'"
                                        :style="elementStyle(el)"
                                        @pointerdown.stop="startDrag($event, el)"
                                    >
                                        <div class="w-full h-full overflow-hidden text-black" :style="elementInnerStyle(el)">
                                            <template x-if="el.type === 'text' || el.type === 'field'">
                                                <span x-text="previewText(el)"></span>
                                            </template>
                                            <template x-if="el.type === 'barcode'">
                                                <svg class="w-full h-full" x-ref="'barcode-' + el.id" x-init="$nextTick(() => renderBarcode(el, $el))"></svg>
                                            </template>
                                            <template x-if="el.type === 'qr'">
                                                <canvas class="w-full h-full" x-init="$nextTick(() => renderQr(el, $el))"></canvas>
                                            </template>
                                            <template x-if="el.type === 'logo'">
                                                <template x-if="logoUrl">
                                                    <img :src="logoUrl" alt="Logo" class="w-full h-full object-contain">
                                                </template>
                                                <template x-if="!logoUrl">
                                                    <span class="text-[10px] text-brand-charcoal">Logo</span>
                                                </template>
                                            </template>
                                            <template x-if="el.type === 'rect'">
                                                <div class="w-full h-full" :style="rectStyle(el)"></div>
                                            </template>
                                            <template x-if="el.type === 'line'">
                                                <div class="w-full" :style="lineStyle(el)"></div>
                                            </template>
                                        </div>
                                        <div
                                            class="absolute right-0 bottom-0 h-3 w-3 bg-brand cursor-se-resize"
                                            x-show="selectedId === el.id"
                                            @pointerdown.stop="startResize($event, el)"
                                        ></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-brand-charcoal dark:text-brand-silver">
                            Canvas scale: <span x-text="scale.toFixed(1)"></span>px per mm. Drag to move, corner handle to resize.
                        </p>
                    </div>
                </div>

                <div class="lg:col-span-3 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <h3 class="font-medium text-sm">Selected element</h3>
                    <template x-if="!selected">
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">Select an element on the canvas.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-2 text-sm">
                            <div class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver" x-text="selected.type"></div>
                            <div class="grid grid-cols-2 gap-2">
                                <label>X
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.x">
                                </label>
                                <label>Y
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.y">
                                </label>
                                <label>W
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.width">
                                </label>
                                <label>H
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.height">
                                </label>
                            </div>

                            <template x-if="selected.type === 'text'">
                                <label class="block">Content
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.content">
                                </label>
                            </template>

                            <template x-if="['field','barcode','qr'].includes(selected.type)">
                                <label class="block">Field
                                    <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fieldKey">
                                        <option value="">—</option>
                                        <template x-for="(label, key) in fieldOptions" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </select>
                                </label>
                            </template>

                            <template x-if="selected.type === 'qr'">
                                <label class="block">Static QR content (if no field)
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.content">
                                </label>
                            </template>

                            <template x-if="selected.type === 'barcode'">
                                <div class="space-y-2">
                                    <label class="block">Symbology
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.symbology">
                                            <option value="code128">Code 128</option>
                                            <option value="code39">Code 39</option>
                                        </select>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" class="rounded text-brand" x-model="selected.props.showText">
                                        Show text
                                    </label>
                                </div>
                            </template>

                            <template x-if="['text','field'].includes(selected.type)">
                                <div class="space-y-2">
                                    <label class="block">Font size
                                        <input type="number" min="6" max="72" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.props.fontSize">
                                    </label>
                                    <label class="block">Weight
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fontWeight">
                                            <option value="normal">Normal</option>
                                            <option value="bold">Bold</option>
                                        </select>
                                    </label>
                                    <label class="block">Align
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.align">
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </label>
                                    <label class="block">Color
                                        <select class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.color">
                                            <option value="#000000">Black</option>
                                            <option value="#444444">Dark gray</option>
                                            <option value="#888888">Gray</option>
                                        </select>
                                    </label>
                                </div>
                            </template>

                            <template x-if="selected.type === 'rect'">
                                <div class="space-y-2">
                                    <label class="block">Stroke
                                        <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.stroke">
                                    </label>
                                    <label class="block">Fill
                                        <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.fill">
                                    </label>
                                </div>
                            </template>

                            <template x-if="selected.type === 'line'">
                                <label class="block">Stroke
                                    <input type="text" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="selected.props.stroke">
                                </label>
                            </template>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <button type="button" class="px-2 py-1 border text-xs" @click="nudgeZ(1)">Bring forward</button>
                                <button type="button" class="px-2 py-1 border text-xs" @click="nudgeZ(-1)">Send back</button>
                                <button type="button" class="px-2 py-1 border text-xs text-red-700" @click="removeSelected()">Delete</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-primary-button>Save layout</x-primary-button>
                <a href="{{ route('shelf-tags.layouts.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
