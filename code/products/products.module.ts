import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ProductsRoutingModule } from './products-routing.module';
import { ProductsComponent } from './products/products.component';
import { ProductDetailsComponent } from './product-details/product-details.component';
import { SharedModule } from '../shared/shared.module';
import { AddproductComponent } from './addproduct/addproduct.component';
import { ngfModule } from "angular-file";
import { ReactiveFormsModule, FormsModule } from '@angular/forms';
import { LaddaModule } from 'angular2-ladda'; 
import { MomentModule } from 'angular2-moment';
import { SlickModule } from 'ngx-slick';
import { MylistComponent } from './mylist/mylist.component';
import { EditComponent } from './edit/edit.component';
@NgModule({
	imports: [
		CommonModule,
		ProductsRoutingModule,
		SharedModule,
		ngfModule,
		ReactiveFormsModule,
		FormsModule,
		LaddaModule.forRoot({
            style: "slide-left",
            spinnerSize: 40,
            spinnerColor: "white",
            spinnerLines: 12
        }),
        MomentModule,
        SlickModule
	],
	declarations: [ProductsComponent, AddproductComponent, ProductDetailsComponent, MylistComponent, EditComponent],
	providers : []
})
export class ProductsModule { }
