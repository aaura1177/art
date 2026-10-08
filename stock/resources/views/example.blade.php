@extends('layouts.app')

@section('content')
<!-- /.box-body -->
            <div class="panel panel-default" id="block_product">
                <div class="panel-heading">Products &nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="button" id="add_product" class="btn btn-primary pull-right" style="margin-top: -7px;" value="Add Product">&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="button" id="add_custom_product" class="btn btn-primary pull-right" style="margin-top: -7px; margin-right: 10px;" value="Add Custom Product">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <!--<th>Days</th>-->
                                <th>Price ($)</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <tr class="alert">
                                <td>1</td>
                                <td>
                                    <select class="form-control" name="products">
                                        <option disabled>-- Select Product --</option>
                                        
                                        <option>Hello</option>
                                        
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="products">
                                        <option disabled>-- Select Product --</option>
                                        
                                        <option>Hello</option>
                                        
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="products">
                                        <option disabled>-- Select Product --</option>
                                        
                                        <option>Hello</option>
                                        
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="products">
                                        <option disabled>-- Select Product --</option>
                                        
                                        <option>Hello</option>
                                        
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="products">
                                        <option disabled>-- Select Product --</option>
                                        
                                        <option>Hello</option>
                                        
                                    </select>
                                </td>
                                
                                
                            </tr>
                           
                        </tbody>
                    </table>
                </div>
            </div>

 @endsection
