import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';
import { ProductsService, EventService } from '../../../services';
import { Router, ActivatedRoute } from '@angular/router';
import { first } from 'rxjs/operators';
import { SnotifyService } from 'ng-snotify';
@Component({
    selector: 'app-addproduct',
    templateUrl: './addproduct.component.html',
    styleUrls: ['./addproduct.component.scss']
})
export class AddproductComponent implements OnInit {
    accept = '*'
    files: File[] = [];
    progress: number
    hasBaseDropZoneOver: boolean = false
    lastFileAt: Date
    addProductForm;
    submitted = false;
    loading = false;
    selected_image : FormControl;
    selected_image_full : FormControl;
    constructor(
        private formBuilder: FormBuilder,
        private _product: ProductsService,
        private _snotify: SnotifyService,
        private route: ActivatedRoute,
        private router: Router,
        private _event : EventService
    ) { }

    ngOnInit() {
        this.addProductForm = this.formBuilder.group({
            name: ['', [Validators.required]],
            price: ['', [Validators.required]],
            description: ['', [Validators.required]],
            image1: ['', [Validators.required]],
            image2: ['',],
            image3: ['',],
            image4: ['',],
            image1_full: ['', [Validators.required]],
            image2_full: ['',],
            image3_full: ['',],
            image4_full: ['',]
        });
    }

    get f() { return this.addProductForm.controls; }

    onSubmit() {
        this.submitted = true;
        // stop here if form is invalid
        if (this.addProductForm.invalid) {
            return;
        }

        this.loading = true;
        this._product.add(this.addProductForm.value)
            .pipe(first())
            .subscribe(
                data => {
                    if (data.result.status) {
                        this._snotify.success(data['result']['msg'], 'Success!');
                        let that = this;
                        setTimeout(function() {
                            that.router.navigate(['products/mylist']);
                        }, 500)
                    }
                },
                error => {
                    // this.error = error;
                    this.loading = false;
                });
    }

    fileChangeEvent(event,image : FormControl, image_full : FormControl) {
        this.selected_image = image;
        this.selected_image_full = image_full;
        var files = event.target.files;
        var file = files[0];
        if (files && file) {
            //Check Image Extension first
            var ValidImageTypes = ["image/gif", "image/jpeg", "image/png"];
            if ((ValidImageTypes.indexOf(file.type)) < 0) {
                this._snotify.error('Please select a valid image.','Error!');
                return;
            }
            var reader = new FileReader();
            reader.onload = this._handleReaderLoaded.bind(this);
            reader.readAsBinaryString(file);
        }
    }


    _handleReaderLoaded(readerEvt) {
        var binaryString = readerEvt.target.result;
        var base64textString = btoa(binaryString);
        var selectedTemp = 'data:image/png;base64,' + base64textString;
        this._event.notifyOther({name:this.selected_image,image:selectedTemp});
        this.selected_image_full.setValue(selectedTemp);
    }


    loadImage(image, image_full){
        this.selected_image = image;
        this._event.notifyOther({name:this.selected_image,image:image_full.value});
    }


    SavedFile(event){
        let dem = event.name as FormControl;
        dem.setValue(event.image);
    }

    deleteImage(con : FormControl, con_full : FormControl) {
        setTimeout(function() {
            con.setValue(null);
        },100)
    }
}
