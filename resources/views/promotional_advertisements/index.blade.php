@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Promotional Advertisements</h2>
            <button class="btn btn-success" onclick="createAd()">Create Advertisement</button>
        </div>
        <div class="card-body">
            <table id="adsTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>Banner</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($ads as $ad)
                    <tr>
                        <td>
                            @if($ad->image)
                                <img src="{{ asset('storage/'.$ad->image) }}" alt="{{ $ad->title }}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;border:2px solid #28a745;cursor:pointer" onclick="previewImage('{{ asset('storage/'.$ad->image) }}','{{ $ad->title }}')">
                            @else
                                <div style="width:60px;height:60px;background:#f8f9fa;border:2px dashed #dee2e6;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#6c757d;">No Img</div>
                            @endif
                        </td>
                        <td>{{ $ad->title }}</td>
                        <td>{{ Str::limit($ad->description ?? 'N/A', 60) }}</td>
                        <td>{{ $ad->start_date ? $ad->start_date->format('M d, Y') : '—' }}</td>
                        <td>{{ $ad->end_date ? $ad->end_date->format('M d, Y') : '—' }}</td>
                        <td>{{ $ad->created_at->format('M d, Y') }}</td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewAd({{ $ad->id }})">View</button>
                            <button class="btn btn-warning btn-sm" onclick="editAd({{ $ad->id }})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteAd({{ $ad->id }})">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $ads->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Minimal styling additions */
    .swal2-popup { width: 750px; }
</style>
<link rel="stylesheet" href="https://unpkg.com/croppie/croppie.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/croppie/croppie.min.js"></script>
<script>
    $(document).ready(function() {
        $('#adsTable').DataTable();
    });

    const adSteps = ['📝','🖼️','⏱️','📋'];
    const AdQueue = Swal.mixin({
        progressSteps: adSteps,
        confirmButtonText: 'Next >',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    let adWizard = {
        data: {},
        croppieInstance: null,
        croppedBlob: null,
        mode: 'create',
        id: null
    };

    function createAd() {
        adWizard = { data:{}, croppieInstance:null, croppedBlob:null, mode:'create', id:null };
        startAdWizard();
    }

    function editAd(id) {
        adWizard = { data:{}, croppieInstance:null, croppedBlob:null, mode:'edit', id:id };
        Swal.fire({ title:'Loading...', allowOutsideClick:false, showConfirmButton:false, html:'<div class="spinner-border text-primary"></div>' });
        $.get('/promotional-advertisements/' + id, function(res) {
            // expects blade view normally; we supply JSON route manually if needed
            // Fallback parse: If HTML returned, abort wizard (user should implement JSON show route for AJAX)
            try {
                if (typeof res === 'object') {
                    adWizard.data = {
                        title: res.title,
                        description: res.description,
                        display_order: res.display_order,
                        start_date: res.start_date,
                        end_date: res.end_date,
                        existing_image: res.image ? res.image : null
                    };
                }
            } catch(e){}
            Swal.close();
            startAdWizard();
        }).fail(() => {
            Swal.fire('Error','Unable to load advertisement.','error');
        });
    }

    async function startAdWizard() {
        try {
            const s1 = await adDetailsStep(); if (!s1.isConfirmed) return;
            const s2 = await adImageStep(); if (!s2.isConfirmed && adWizard.mode==='create') return;
            const s3 = await adScheduleStep(); if (!s3.isConfirmed) return;
            const s4 = await adReviewStep(); if (s4.isConfirmed) await submitAd();
        } catch(e) {
            // Go back logic not implemented for brevity
        }
    }

    function adDetailsStep() {
        return AdQueue.fire({
            title: '📝 Advertisement Details',
            currentProgressStep: 0,
            html: `
                <div style="text-align:left;">
                    <label class="fw-bold mt-2">Title *</label>
                    <input id="ad-title" class="swal2-input" placeholder="Title" value="${adWizard.data.title||''}">
                    <label class="fw-bold mt-2">Description</label>
                    <textarea id="ad-description" class="swal2-textarea" placeholder="Description">${adWizard.data.description||''}</textarea>
                </div>
            `,
            preConfirm: () => {
                const title = $('#ad-title').val().trim();
                if (!title) {
                    Swal.showValidationMessage('Title required');
                    return false;
                }
                adWizard.data.title = title;
                adWizard.data.description = $('#ad-description').val();
                return true;
            }
        });
    }

    function adImageStep() {
        return AdQueue.fire({
            title: '🖼️ Banner Image',
            currentProgressStep: 1,
            showDenyButton: true,
            denyButtonText: adWizard.mode==='edit' ? 'Keep Existing' : 'Skip Image',
            html: `
                <div style="text-align:left;">
                    <input id="ad-image-input" type="file" accept="image/*" style="width:100%;padding:10px;border:2px solid #e8f4f8;border-radius:8px;">
                    <small class="text-muted">Max 2MB. JPG/PNG/GIF.</small>
                    <div id="ad-crop-wrapper" style="display:none;margin-top:15px;">
                        <div id="ad-croppie" style="width:100%;height:350px;border:2px solid #e8f4f8;border-radius:8px;"></div>
                        <div class="text-center mt-3">
                            <button type="button" id="ad-crop-btn" class="btn btn-success btn-sm">✂️ Crop</button>
                            <button type="button" id="ad-reset-btn" class="btn btn-warning btn-sm">🔄 Reset</button>
                        </div>
                    </div>
                    <div id="ad-cropped-preview" style="display:none;text-align:center;margin-top:15px;">
                        <p class="text-success fw-bold mb-2">✅ Image Ready</p>
                        <img id="ad-preview-img" style="width:160px;height:160px;object-fit:cover;border:3px solid #28a745;border-radius:8px;">
                        <div class="mt-2">
                            <button type="button" id="ad-change-img" class="btn btn-secondary btn-sm">Change</button>
                        </div>
                    </div>
                    ${adWizard.data.existing_image ? `
                        <div class="mt-3">
                            <strong>Current Image:</strong><br>
                            <img src="${adWizard.data.existing_image.startsWith('http') ? adWizard.data.existing_image : ('/storage/'+adWizard.data.existing_image)}" style="width:100px;height:100px;object-fit:cover;border:2px solid #3498db;border-radius:6px;">
                        </div>` : ''
                    }
                </div>
            `,
            didOpen: () => initCroppie(),
            preConfirm: () => {
                if (!adWizard.croppedBlob && !adWizard.data.existing_image) {
                    Swal.showValidationMessage('Provide or keep an image (or skip if allowed).');
                    return false;
                }
                return true;
            },
            preDeny: () => true
        });
    }

    function initCroppie() {
        const input = document.getElementById('ad-image-input');
        const wrap = document.getElementById('ad-crop-wrapper');
        const previewWrap = document.getElementById('ad-cropped-preview');
        const previewImg = document.getElementById('ad-preview-img');

        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 2*1024*1024) {
                Swal.showValidationMessage('Image exceeds 2MB');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                wrap.style.display='block';
                previewWrap.style.display='none';
                if (adWizard.croppieInstance) adWizard.croppieInstance.destroy();
                // CHANGED: square viewport for 500x500 output
                adWizard.croppieInstance = new Croppie(document.getElementById('ad-croppie'), {
                    viewport: { width:300, height:300, type:'square' },
                    boundary: { width: '100%', height:350 },
                    showZoomer: true
                });
                adWizard.croppieInstance.bind({ url: ev.target.result });
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('ad-crop-btn').addEventListener('click', () => {
            if (!adWizard.croppieInstance) return;
            // CHANGED: output size now 500x500 square
            adWizard.croppieInstance.result({
                type:'blob',
                size:{ width:500, height:500 },
                format:'png',
                quality:0.9
            }).then(blob => {
                adWizard.croppedBlob = blob;
                const url = URL.createObjectURL(blob);
                previewImg.src = url;
                wrap.style.display='none';
                previewWrap.style.display='block';
            });
        });

        document.getElementById('ad-reset-btn').addEventListener('click', () => {
            if (adWizard.croppieInstance) {
                adWizard.croppieInstance.destroy();
                adWizard.croppieInstance=null;
            }
            adWizard.croppedBlob=null;
            input.value='';
            wrap.style.display='none';
            previewWrap.style.display='none';
        });

        document.getElementById('ad-change-img').addEventListener('click', () => {
            previewWrap.style.display='none';
            input.value='';
            adWizard.croppedBlob=null;
        });
    }

    function adScheduleStep() {
        return AdQueue.fire({
            title: '⏱️ Schedule',
            currentProgressStep: 2,
            html: `
                <div style="text-align:left;">
                    <label class="fw-bold mt-2">Start Date</label>
                    <input id="ad-start" type="datetime-local" class="swal2-input" value="${formatForInput(adWizard.data.start_date)||''}">
                    <label class="fw-bold mt-2">End Date</label>
                    <input id="ad-end" type="datetime-local" class="swal2-input" value="${formatForInput(adWizard.data.end_date)||''}">
                    <small class="text-muted">Leave blank for open-ended display.</small>
                </div>
            `,
            preConfirm: () => {
                const start = $('#ad-start').val();
                const end = $('#ad-end').val();
                if (start && end && new Date(start) > new Date(end)) {
                    Swal.showValidationMessage('End date must be after start date');
                    return false;
                }
                adWizard.data.start_date = start;
                adWizard.data.end_date = end;
                return true;
            }
        });
    }

    function adReviewStep() {
        const imgPreview = adWizard.croppedBlob
            ? `<img src="${URL.createObjectURL(adWizard.croppedBlob)}" style="width:180px;height:180px;object-fit:cover;border:2px solid #28a745;border-radius:6px;">`
            : (adWizard.data.existing_image
                ? `<img src="${adWizard.data.existing_image.startsWith('http')?adWizard.data.existing_image:'/storage/'+adWizard.data.existing_image}" style="width:180px;height:180px;object-fit:cover;border:2px solid #3498db;border-radius:6px;">`
                : '<div style="width:180px;height:180px;background:#f8f9fa;border:2px dashed #dee2e6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#6c757d;">No Image</div>');

        return AdQueue.fire({
            title: '📋 Review',
            currentProgressStep: 3,
            confirmButtonText: adWizard.mode==='edit' ? '✅ Update' : '✅ Create',
            showDenyButton: true,
            denyButtonText: '← Back',
            html: `
                <div style="text-align:left;background:#f8f9fa;padding:20px;border-radius:10px;">
                    <h4>${adWizard.data.title}</h4>
                    <p style="color:#7f8c8d;">${adWizard.data.description || 'No description'}</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div><strong>Start:</strong> ${adWizard.data.start_date || '—'}</div>
                        <div><strong>End:</strong> ${adWizard.data.end_date || '—'}</div>
                    </div>
                    <div class="mt-3"><strong>Banner:</strong><br>${imgPreview}</div>
                </div>
            `,
            preDeny: () => { throw new Error('back'); }
        });
    }

    async function submitAd() {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('title', adWizard.data.title);
        formData.append('description', adWizard.data.description || '');
        if (adWizard.data.start_date) formData.append('start_date', adWizard.data.start_date);
        if (adWizard.data.end_date) formData.append('end_date', adWizard.data.end_date);
        if (adWizard.croppedBlob) {
            formData.append('image', adWizard.croppedBlob, 'banner.png');
        }

        const url = adWizard.mode==='edit'
            ? '/promotional-advertisements/' + adWizard.id
            : '/promotional-advertisements';
        const method = adWizard.mode==='edit' ? 'POST' : 'POST';

        if (adWizard.mode==='edit') formData.append('_method','PUT');

        Swal.fire({
            title: adWizard.mode==='edit'?'Updating...':'Creating...',
            allowOutsideClick:false,
            showConfirmButton:false,
            html:'<div class="spinner-border text-success"></div>'
        });

        $.ajax({
            url,
            type: method,
            data: formData,
            processData:false,
            contentType:false,
            success: () => {
                Swal.fire('Success', adWizard.mode==='edit'?'Advertisement updated.':'Advertisement created.','success')
                    .then(()=> location.reload());
            },
            error: (xhr) => {
                let msg = 'Server Error';
                if (xhr.status===422) {
                    const errs = xhr.responseJSON.errors;
                    msg = Object.keys(errs).map(k=>errs[k].join(', ')).join('\n');
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    }

    function viewAd(id) {
        Swal.fire({
            title:'Loading...',
            showConfirmButton:false,
            allowOutsideClick:false,
            html:'<div class="spinner-border text-primary"></div>'
        });
        $.get('/promotional-advertisements/' + id, function(html) {
            // If JSON needed, adapt; for now display raw HTML (server should render show view).
            Swal.fire({
                title:'Advertisement',
                html: html,
                width: '800px',
                confirmButtonText:'Close'
            });
        }).fail(()=> Swal.fire('Error','Unable to load advertisement.','error'));
    }

    function deleteAd(id) {
        Swal.fire({
            title:'Delete Advertisement?',
            icon:'warning',
            html:'<p>This action cannot be undone.</p>',
            showCancelButton:true,
            confirmButtonColor:'#e74c3c',
            confirmButtonText:'Yes, delete',
        }).then(res=>{
            if (!res.isConfirmed) return;
            Swal.fire({ title:'Deleting...', showConfirmButton:false, allowOutsideClick:false, html:'<div class="spinner-border text-danger"></div>' });
            $.ajax({
                url:'/promotional-advertisements/' + id,
                type:'POST',
                data:{ _token:'{{ csrf_token() }}', _method:'DELETE' },
                success: ()=> Swal.fire('Deleted','Advertisement removed.','success').then(()=>location.reload()),
                error: ()=> Swal.fire('Error','Deletion failed.','error')
            });
        });
    }

    function previewImage(src,title) {
        Swal.fire({
            title: title,
            imageUrl: src,
            imageAlt: title,
            showConfirmButton:false,
            showCloseButton:true,
            width:'70%'
        });
    }

    function formatForInput(dt) {
        if (!dt) return null;
        // Expecting ISO or carbon string
        let d = new Date(dt);
        if (isNaN(d.getTime())) return null;
        const pad = n => (n<10?'0':'')+n;
        return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
    }
</script>
@endpush
