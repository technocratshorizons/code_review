import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { ProductsComponent } from './products/products.component';
import { AddproductComponent } from './addproduct/addproduct.component';
import { ProductDetailsComponent } from './product-details/product-details.component';
import { MylistComponent } from './mylist/mylist.component';
import { EditComponent } from './edit/edit.component';
import { AuthGuard } from '../../guards'
const routes: Routes = [
	{
		path: '',
		redirectTo: 'list',
		pathMatch: 'full'
	},
	{
		path: 'list/:id',
		component: ProductsComponent
	},
	{
		path: 'list',
		component: ProductsComponent
	},
	{
		path: 'add',
		component: AddproductComponent,
		canActivate : [AuthGuard] 
	},
	{
		path: 'mylist',
		component: MylistComponent,
		canActivate : [AuthGuard] 
	},
	{
		path: 'single/:product_id',
		component: ProductDetailsComponent
	},
	{
		path: 'edit/:product_id',
		component: EditComponent,
		canActivate : [AuthGuard] 
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class ProductsRoutingModule { }
