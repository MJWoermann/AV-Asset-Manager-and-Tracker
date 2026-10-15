@php
    use App\Support\ShelfTagPaperPresets;

    $editorSettingsRaw = old('editor_settings', $layout->editor_settings);
    if (is_string($editorSettingsRaw)) {
        $editorSettingsRaw = json_decode($editorSettingsRaw, true);
    }
    $initial = [
        'name' => old('name', $layout->name),
        'paper_preset' => old('paper_preset', $layout->paper_preset),
        'width_mm' => (float) old('width_mm', $layout->width_mm),
        'height_mm' => (float) old('height_mm', $layout->height_mm),
        'orientation' => old('orientation', $layout->orientation ?: 'portrait'),
        'length_mode' => old('length_mode', $layout->length_mode ?: ShelfTagPaperPresets::LENGTH_MODE_FIXED),
        'elements' => old('elements')
            ? (is_string(old('elements')) ? json_decode(old('elements'), true) : old('elements'))
            : ($layout->elements ?? []),
        'logo_url' => $layout->logoUrl(),
        'editor_settings' => ShelfTagPaperPresets::normalizeEditorSettings(
            is_array($editorSettingsRaw) ? $editorSettingsRaw : null
        ),
    ];
    if (! is_array($initial['elements'])) {
        $initial['elements'] = [];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $layout->exists ? 'Edit shelf tag layout' : 'New shelf tag layout' }}</h2>
            <a href="{{ route('shelf-tags.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Back to shelf tags</a>
        </div>
    </x-slot>

    <div
        class="max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8"
        x-data="shelfTagDesigner(@js($initial), @js($presets), @js($fieldOptions))"
        @keydown.window="onKeyDown($event)"
        @keyup.window="onKeyUp($event)"
    >
        @if (session('status'))
            <p class="mb-3 text-sm text-brand">{{ session('status') }}</p>
        @endif
        @if (session('import_warnings'))
            <div class="mb-3 border border-amber-500/40 bg-amber-50 dark:bg-amber-950/30 p-3 text-sm text-amber-900 dark:text-amber-100 space-y-1">
                <p class="font-medium">Import notes</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ((array) session('import_warnings') as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
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
            @submit="syncFormFields()"
        >
            @csrf
            @if($layout->exists) @method('PUT') @endif
            <input type="hidden" name="elements" x-ref="elementsInput" :value="elementsJson">
            <input type="hidden" name="editor_settings" x-ref="editorSettingsInput" :value="editorSettingsJson">
            <input type="hidden" name="length_mode" :value="lengthMode">

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <div class="xl:col-span-3 space-y-3 bg-white dark:bg-brand-slate border p-3">
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
                            <x-text-input
                                id="height_mm"
                                name="height_mm"
                                type="number"
                                step="0.1"
                                min="10"
                                class="mt-1 block w-full"
                                x-model.number="heightMm"
                                x-on:change="onHeightManualEdit()"
                                x-bind:readonly='isContinuous && lengthMode === "auto"'
                                required
                            />
                        </div>
                    </div>
                    <div x-show="isContinuous" x-cloak>
                        <x-input-label for="length_mode_ui" value="Label length" />
                        <select
                            id="length_mode_ui"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white"
                            x-model="lengthMode"
                            @change="onLengthModeChange()"
                        >
                            <option value="fixed">Fixed</option>
                            <option value="auto">Auto (fit content)</option>
                        </select>
                        <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">
                            Auto sizes length from the lowest element (+{{ 2 }} mm pad), clamped {{ ShelfTagPaperPresets::CONTINUOUS_LENGTH_MIN_MM }}–{{ ShelfTagPaperPresets::CONTINUOUS_LENGTH_MAX_MM }} mm.
                            Dragging past the bottom also extends the label.
                        </p>
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

                <div class="xl:col-span-7 space-y-3">
                    <div class="bg-white dark:bg-brand-slate border p-3">
                        <div class="flex flex-wrap gap-2 mb-3 text-sm">
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('text')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7V5h16v2M9 19h6M12 5v14" />
                                </svg>
                                Text
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('field')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h10M4 17h16" />
                                </svg>
                                Field
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('barcode')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6v12M7 6v12M10 6v12M12 6v12M15 6v12M18 6v12M20 6v12" />
                                </svg>
                                Barcode
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('qr')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 3h3v3h-3v-3zm4-3h3v3h-3v-3zm-4 0h3v2h-3v-2zm4 4h3v3h-3v-3z" />
                                </svg>
                                QR
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('rect')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="5" y="6" width="14" height="12" stroke-width="1.75" />
                                </svg>
                                Rect
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('line')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-width="1.75" d="M4 12h16" />
                                </svg>
                                Line
                            </button>
                            <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 border" @click="addElement('logo')">
                                <svg class="h-4 w-4 shrink-0 text-brand-charcoal dark:text-brand-silver" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.5-4.5a2 2 0 012.8 0L16 16m-2-2l1.5-1.5a2 2 0 012.8 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Logo
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3 text-sm border-t border-brand-silver dark:border-brand-charcoal pt-3">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="rounded text-brand" x-model="showGrid">
                                Show grid
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" class="rounded text-brand" x-model="snapToGrid">
                                Snap to grid
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <span class="text-brand-charcoal dark:text-brand-silver">Grid</span>
                                <select
                                    class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white text-sm py-1"
                                    x-model.number="gridSizeMm"
                                >
                                    <option :value="2.5">2.5 mm</option>
                                    <option :value="5">5 mm</option>
                                    <option :value="10">10 mm</option>
                                </select>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mb-3 text-sm border-t border-brand-silver dark:border-brand-charcoal pt-3">
                            <span class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver mr-1">Zoom</span>
                            <button type="button" class="px-2 py-1 border" @click="zoomOut()" title="Zoom out">−</button>
                            <button type="button" class="px-2 py-1 border min-w-[4.5rem]" @click="resetZoom()" x-text="zoomPercent + '%'"></button>
                            <button type="button" class="px-2 py-1 border" @click="zoomIn()" title="Zoom in">+</button>
                            <button type="button" class="px-2 py-1 border text-xs" @click="centerView()">Centre</button>
                            <span class="text-xs text-brand-charcoal dark:text-brand-silver ml-1">Ctrl+wheel · Space+drag / middle-click to pan</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mb-3 text-sm border-t border-brand-silver dark:border-brand-charcoal pt-3">
                            <span class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver mr-1">Align</span>
                            <div class="inline-flex border overflow-hidden">
                                <button type="button" class="px-2 py-1 text-xs" :class="alignTarget === 'group' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'group'">Selection</button>
                                <button type="button" class="px-2 py-1 text-xs border-l" :class="alignTarget === 'biggest' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'biggest'">Biggest</button>
                                <button type="button" class="px-2 py-1 text-xs border-l" :class="alignTarget === 'page' ? 'bg-brand-charcoal text-white' : ''" @click="alignTarget = 'page'">Page</button>
                            </div>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('left')" title="Align left">⇤</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('center')" title="Align centre H">↔</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('right')" title="Align right">⇥</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('top')" title="Align top">⤒</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('middle')" title="Align middle V">↕</button>
                            <button type="button" class="px-2 py-1 border text-xs" :disabled="!canAlign" :class="!canAlign && 'opacity-40 cursor-not-allowed'" @click="align('bottom')" title="Align bottom">⤓</button>
                            <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="selectedCount > 0" x-text="selectedCount + ' selected'"></span>
                            <span class="text-xs text-brand-charcoal dark:text-brand-silver" x-show="alignTarget !== 'page' && selectedCount === 1">Shift-click or marquee for multi-select</span>
                        </div>

                        <div
                            class="overflow-hidden rounded border-2 border-brand-charcoal dark:border-brand-black bg-brand-charcoal dark:bg-black shadow-inner relative h-[min(70vh,560px)]"
                            x-ref="viewport"
                            :style="{ cursor: viewportCursor }"
                            @wheel="onViewportWheel($event)"
                            @pointerdown="onViewportPointerDown($event)"
                        >
                            <div class="absolute top-0 left-0" :style="viewportTransformStyle">
                                <div
                                    class="relative bg-white shadow-md ring-1 ring-black/20 overflow-hidden"
                                    x-ref="canvas"
                                    :style="canvasStyle"
                                    @pointerdown.stop="onCanvasPointerDown($event)"
                                >
                                    <div class="relative" :style="labelContentStyle">
                                    <div
                                        x-show="showGrid"
                                        class="absolute inset-0 pointer-events-none z-[1]"
                                        :style="gridOverlayStyle"
                                        aria-hidden="true"
                                    >
                                        <div class="absolute top-0 bottom-0 w-px bg-brand" :style="centerCrosshairStyleX"></div>
                                        <div class="absolute left-0 right-0 h-px bg-brand" :style="centerCrosshairStyleY"></div>
                                    </div>
                                    <div
                                        x-show="marquee"
                                        class="absolute z-[4] border border-brand bg-brand/10 pointer-events-none"
                                        :style="marqueeStyle"
                                    ></div>
                                    <template x-for="el in sortedElements" :key="el.id">
                                        <div
                                            class="absolute border border-transparent select-none z-[2]"
                                            :data-el-id="el.id"
                                            :class="isSelected(el) ? 'ring-2 ring-brand border-brand' : 'hover:border-brand-charcoal/40'"
                                            :style="elementStyle(el)"
                                            @pointerdown.stop="startDrag($event, el)"
                                        >
                                            <div class="w-full h-full overflow-hidden text-black" :style="elementInnerStyle(el)">
                                                <template x-if="el.type === 'text' || el.type === 'field'">
                                                    <span x-text="previewText(el)"></span>
                                                </template>
                                                <template x-if="el.type === 'barcode'">
                                                    <div class="w-full h-full flex items-center justify-center overflow-hidden">
                                                        <svg
                                                            class="shelf-tag-designer-barcode"
                                                            x-init="$watch(() => [el.width, el.height, el.props.symbology, el.props.showText, el.props.fieldKey], () => renderBarcode(el, $el)); $nextTick(() => renderBarcode(el, $el))"
                                                        ></svg>
                                                    </div>
                                                </template>
                                                <template x-if="el.type === 'qr'">
                                                    <div class="w-full h-full flex items-center justify-center overflow-hidden">
                                                        <canvas x-init="$watch(() => [el.width, el.height, el.props.fieldKey, el.props.content], () => renderQr(el, $el)); $nextTick(() => renderQr(el, $el))"></canvas>
                                                    </div>
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
                                                x-show="selectedCount === 1 && isSelected(el)"
                                                @pointerdown.stop="startResize($event, el)"
                                            ></div>
                                        </div>
                                    </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-brand-charcoal dark:text-brand-silver">
                            Zoom <span x-text="zoomPercent"></span>% · base <span x-text="scale.toFixed(1)"></span>px/mm.
                            Grid from label centre. Shift-click or drag-marquee to multi-select.
                            <span x-show="snapToGrid">Snapping to <span x-text="gridSizeMm"></span> mm.</span>
                            <span x-show="showGrid && !snapToGrid">Grid guides only.</span>
                            Barcodes scale to fit their box without cropping.
                        </p>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-3 bg-white dark:bg-brand-slate border p-3">
                    <h3 class="font-medium text-sm">
                        <template x-if="selectedCount > 1">
                            <span x-text="selectedCount + ' elements'"></span>
                        </template>
                        <template x-if="selectedCount <= 1">
                            <span>Selected element</span>
                        </template>
                    </h3>
                    <template x-if="selectedCount > 1">
                        <div class="space-y-2 text-sm">
                            <p class="text-brand-charcoal dark:text-brand-silver">Use Align tools above. Delete removes all selected.</p>
                            <button type="button" class="px-2 py-1 border text-xs text-red-700" @click="removeSelected()">Delete selected</button>
                        </div>
                    </template>
                    <template x-if="selectedCount === 0">
                        <p class="text-sm text-brand-charcoal dark:text-brand-silver">Select an element on the canvas.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-2 text-sm">
                            <div class="text-xs uppercase tracking-wide text-brand-charcoal dark:text-brand-silver" x-text="selected.type"></div>
                            <div class="grid grid-cols-2 gap-2">
                                <label>X
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.x" @change="afterGeometryChange([selected])">
                                </label>
                                <label>Y
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.y" @change="afterGeometryChange([selected])">
                                </label>
                                <label>W
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.width" @change="afterGeometryChange([selected])">
                                </label>
                                <label>H
                                    <input type="number" step="0.1" class="mt-1 w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="selected.height" @change="afterGeometryChange([selected])">
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
                <a href="{{ route('shelf-tags.index') }}" class="text-sm text-brand-charcoal dark:text-brand-silver">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
