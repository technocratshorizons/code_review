<style type="text/css">
   html, body, #map-canvas {
          height: 100%;
          margin: 0px;
          padding: 0px
        }
        .controls {
          margin-top: 16px;
          border: 1px solid transparent;
          border-radius: 2px 0 0 2px;
          box-sizing: border-box;
          -moz-box-sizing: border-box;
          height: 32px;
          outline: none;
          box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        #pac-input {
          background-color: #fff;
          font-family: Roboto;
          font-size: 15px;
          font-weight: 300;
          margin-left: 12px;
          padding: 0 11px 0 13px;
          text-overflow: ellipsis;
          width: 400px;
        }

        #pac-input:focus {
          border-color: #4d90fe;
        }

        .pac-container {
          font-family: Roboto;
        }

        #type-selector {
          color: #fff;
          background-color: #4d90fe;
          padding: 5px 11px 0px 11px;
        }

        #type-selector label {
          font-family: Roboto;
          font-size: 13px;
          font-weight: 300;
        }
        .hid{
            display: none;
        }
        .shw{
            display: block !important;
        }
</style>

@extends('layouts.header_without_login')
@section('title', 'POST RENTAL | RENT WELL')
@section('content')
<!-- banner start here -->
@if($user->user_type == 'tenant')
<div class="banner-main">
   <div class="no-banner">
   </div>
</div>
@endif
<!-- banner end here -->
<!-- top bar section -->
@if($user->user_type == 'landlord')
<div class="banner-main">
   <div class="edit-rental-banner">
   </div>
</div>
@include('elements.header_top_bar')
@endif
<!-- top bar section -->
<div class="post-main">
   <div class="container">
      <div class="row">
         <div class="container">
            <div class="stepwizard form-setp-wizard">
               <div class="stepwizard-row setup-panel">
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-1" type="button" class="btn btn-success btn-circle">1</a>
                     <!--         <p><small>Shipper</small></p> -->
                  </div>
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                     <!--   <p><small>Destination</small></p> -->
                  </div>
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-3" type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
                     <!--     <p><small>Schedule</small></p> -->
                  </div>
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-4" type="button" class="btn btn-default btn-circle" disabled="disabled">4</a>
                     <!--  <p><small>Cargo</small></p> -->
                  </div>
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-5" type="button" class="btn btn-default btn-circle" disabled="disabled">5</a>
                     <!--    <p><small>Cargo</small></p> -->
                  </div>
                  <div class="stepwizard-step col-xs-2">
                     <a href="#step-6" type="button" class="btn btn-default btn-circle" disabled="disabled">6</a>
                     <!--            <p><small>Cargo</small></p> -->
                  </div>
               </div>
            </div>
            <form role="form">
               <div class="panel  setup-content" id="step-1">
                  <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">
                     <div class="sec_heading_div">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <h1 class="sec_heading_left">POST RENTAL</h1>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12 text-right">
                           <a class="locate-on-map"> <i class="fa fa-map-marker" aria-hidden="true"></i> Locate On map</a>
                        </div>
                     </div>
                     <div class="col-md-12 col-xs-12 col-sm-12 display-map">
                        <input id="pac-input" class="controls" type="text" placeholder="Search Box">

                        <div  id="map-canvas"></div>
                       <!--  <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d369970.60921537044!2d-79.77522460608456!3d43.57748014773716!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b469fe76b05b7%3A0x3146cbed75966db!2z4KSu4KS_4KS44KS_4KS44KWJ4KSX4KS-LCDgpjpg') }}pKjgpY3gpJ_gpL7gpLDgpL_gpK_gpYssIOCkleCkqOCkvuCkoeCkvg!5e0!3m2!1shi!2sin!4v1525162127492" width="100%" height="365" frameborder="0" style="border:0" allowfullscreen></iframe> -->
                     </div>
                     <div class="address-wrapper">
                        <div class="address-section ">
                           <div class="col-md-6 col-sm-6 col-xs-12">
                              <div class="form-group form-h">
                                 <label for="name">Full Address  <span class="asteric">*</span></label>
                                 <input type="text"  class="form-control addr address" id="address" name="address" value="{{ $user->address }}">
                                 <label id="address-error" class="error" for="address"><?php echo ($errors->has('address'))?$errors->first('address'):'';?></label>
                              </div>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">CITY <span class="asteric">*</span></label>
                              <input type="text" readonly class="form-control" id="city" name="city" value="{{ $user->city }}">
                              <label id="city-error" class="error" for="city"><?php echo ($errors->has('city'))?$errors->first('city'):'';?></label>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="street">STREET <span class="asteric">*</span></label>
                              <input type="text" value="{{ $user->street }}" class="form-control" id="street"  name="street">
                              
                              <label id="street-error" class="error" for="street"><?php echo ($errors->has('street'))?$errors->first('street'):'';?></label>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="postal_code">POSTAL CODE <span class="asteric">*</span></label>
                              <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $user->postal_code }}">
                              <label id="city-error" class="error" for="postal_code"><?php echo ($errors->has('postal_code'))?$errors->first('postal_code'):'';?></label>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="checkbox checkbox-primary custom-checkbox">
                              <input id="checkbox2" type="checkbox" >
                              <label for="checkbox2">
                              HIDE ADDRESS
                              </label>
                           </div>
                        </div>
                     </div>
                        
                     </div>
                     <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                        <button class="btn btn-default next-button nextBtn " type="button">NEXT</button>
                     </div>
                  </div>
                  <!--     <button class="btn btn-primary nextBtn pull-right" type="button">Next</button> -->
               </div>
               <div class="panel  setup-content" id="step-2">
                  <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">
                     <div class="sec_heading_div">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <h1 class="sec_heading_left">tell us a little about the property</h1>
                        </div>
                     </div>
                     <div class="col-md-12 col-sm-12 col-xs-12 p0 ">
                        <div class="col-sm-12 col-xs-12 col-md-12 radio-pair">
                           <ul class="list-unstyled list-inline on-off-ul">
                              <li>
                                 <div class="btn-group" id="status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on active">
                                    <input type="radio" value="1" name="multifeatured_module[module_id][status]" checked="checked">apartment / condo</label>
                                    <label class="btn btn-default btn-off">
                                    <input type="radio" value="0" name="multifeatured_module[module_id][status]">duplex / house</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="btn-group" id="status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on active">
                                    <input type="radio" value="1" name="multifeatured_module[module_id][status]" checked="checked">furnished</label>
                                    <label class="btn btn-default btn-off">
                                    <input type="radio" value="0" name="multifeatured_module[module_id][status]">unfurnished</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="btn-group" id="status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on active">
                                    <input type="radio" value="1" name="multifeatured_module[module_id][status]" checked="checked">Smoking</label>
                                    <label class="btn btn-default btn-off">
                                    <input type="radio" value="0" name="multifeatured_module[module_id][status]">non smoking</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="btn-group" id="status" data-toggle="buttons">
                                    <label class="btn btn-default btn-on active">
                                    <input type="radio" value="1" name="multifeatured_module[module_id][status]" checked="checked"><i class="fa fa-paw" aria-hidden="true"></i> ALLOWED</label>
                                    <label class="btn btn-default btn-off">
                                    <input type="radio" value="0" name="multifeatured_module[module_id][status]"> <i class="fa fa-paw" aria-hidden="true"></i> noT ALLOWED</label>
                                 </div>
                              </li>
                           </ul>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">bedroom <span class="asteric">*</span></label>
                              <select class="form-control" id="city" class="city">
                                 <option></option>
                                 <option>london</option>
                                 <option>Delhi</option>
                                 <option>Paris</option>
                                 <option>perth</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">bathroom  <span class="asteric">*</span></label>
                              <select class="form-control" id="city" class="city">
                                 <option></option>
                                 <option>london</option>
                                 <option>Delhi</option>
                                 <option>Paris</option>
                                 <option>perth</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                           <div class="form-group form-h">
                              <label for="name">enter area SQ.FT. <span class="asteric">*</span></label>
                              <input type="name" class="form-control" id="name"  name="name">
                           </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">bathroom  <span class="asteric">*</span></label>
                              <select class="form-control" id="city" class="city">
                                 <option></option>
                                 <option>2</option>
                                 <option>2</option>
                                 <option>2</option>
                                 <option>2</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">bathroom  <span class="asteric">*</span></label>
                              <select class="form-control" id="city" class="city">
                                 <option></option>
                                 <option>3</option>
                                 <option>3</option>
                                 <option>3</option>
                                 <option>3</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                           <button class="btn btn-default previous-button  " type="button">PREVIOUS</button>
                           <button class="btn btn-default next-button nextBtn " type="button">NEXT</button>
                        </div>
                     </div>
                  </div>
                  <!--   <button class="btn btn-primary nextBtn pull-right" type="button">Next</button> -->
               </div>
               <div class="panel  setup-content" id="step-3">
                  <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">
                     <div class="sec_heading_div">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <h1 class="sec_heading_left">rental details</h1>
                        </div>
                     </div>
                     <div class="col-md-4 col-sm-4 col-xs-12 p0">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <div class="form-group form-h">
                              <label>enter monthly rent <span class="asteric">*</span></label>
                              <div class="input-group">
                                 <div class="input-group-addon">$</div>
                                 <input type="text" class="form-control">
                              </div>
                           </div>
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <div class="form-group form-h">
                              <label>enter security deposit  <span class="asteric">*</span></label>
                              <div class="input-group">
                                 <div class="input-group-addon">$</div>
                                 <input type="text" class="form-control">
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-md-8 col-sm-8 col-xs-12">
                        <div class="form-group f-textarea-s">
                           <label >RENTAL INCENTIVES IF APPLICABLE</label>
                           <textarea class="form-control textarea-size" rows="5" id="comment"></textarea>
                        </div>
                     </div>
                     <div class="col-md-2 col-sm-2 col-xs-12 on-off-ul yes-no">
                        <label class="label-sh">UTILITIES included</label>
                        <div class="btn-group" id="status" data-toggle="buttons">
                           <label class="btn btn-default btn-on active">
                           <input type="radio" value="1" name="multifeatured_module[module_id][status]" checked="checked">Yes</label>
                           <label class="btn btn-default btn-off">
                           <input type="radio" value="0" name="multifeatured_module[module_id][status]">No</label>
                        </div>
                     </div>
                     <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="ui fluid selection search dropdown multiple selct-2">
                           <input name="tags" type="hidden">
                           <div class="default text">Select</div>
                           <i class="dropdown icon"></i>
                           <div class="menu">
                              <div class="item" data-value="angular">Angular</div>
                              <div class="item" data-value="css">CSS</div>
                              <div class="item" data-value="design">Graphic Design</div>
                              <div class="item" data-value="ember">Ember</div>
                              <div class="item" data-value="html">HTML</div>
                              <div class="item" data-value="ia">Information Architecture</div>
                              <div class="item" data-value="javascript">Javascript</div>
                              <div class="item" data-value="mech">Mechanical Engineering</div>
                              <div class="item" data-value="meteor">Meteor</div>
                              <div class="item" data-value="node">NodeJS</div>
                              <div class="item" data-value="plumbing">Plumbing</div>
                              <div class="item" data-value="python">Python</div>
                              <div class="item" data-value="rails">Rails</div>
                              <div class="item" data-value="react">React</div>
                              <div class="item" data-value="repair">Kitchen Repair</div>
                              <div class="item" data-value="ruby">Ruby</div>
                              <div class="item" data-value="ui">UI Design</div>
                              <div class="item" data-value="ux">User Experience</div>
                           </div>
                        </div>
                     </div>
                     <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                        <button class="btn btn-default previous-button  " type="button">PREVIOUS</button>
                        <button class="btn btn-default next-button nextBtn " type="button">NEXT</button>
                     </div>
                  </div>
                  <!--    <button class="btn btn-primary nextBtn pull-right" type="button">Next</button> -->
               </div>
               <div class="panel  setup-content" id="step-4">
                  <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">
                     <div class="sec_heading_div">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <h1 class="sec_heading_left upload-txts">upload property pictures</h1>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12 text-right">
                           <button class="btn btn-default browse-trigger"> browse files </button>
                        </div>
                     </div>
                     <div class="col-md-12 col-sm-12 col-xs-12 multi-files">
                        <input id="demo" type="file" name="files" accept=".jpg, .png, image/jpeg, image/png" multiple>
                     </div>
                     <div class="col-md-12 col-sm-12 col-xs-12 ">
                        <div class="form-group f-textarea-s">
                           <label class="lbl-des">property description <span class="asteric">*</span></label>
                           <textarea class="form-control textarea-size" rows="5" id="comment"></textarea>
                        </div>
                     </div>
                     <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                        <button class="btn btn-default previous-button  " type="button">PREVIOUS</button>
                        <button class="btn btn-default next-button nextBtn " type="button">NEXT</button>
                     </div>
                  </div>
               </div>
               <div class="panel  setup-content" id="step-5">
                  <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">
                     <div class="sec_heading_div">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <h1 class="sec_heading_left">rental details</h1>
                        </div>
                     </div>
                     <div class="col-md-8 col-sm-12 col-xs-12 p0">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="name">available from <span class="asteric">*</span></label>
                              <div class='input-group date' id='datetimepicker1'>
                                 <input type='text' class="form-control" />
                                 <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                                 </span>
                              </div>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="name">term of lease  <span class="asteric">*</span></label>
                              <input type="name" class="form-control" id="name"  name="name">
                           </div>
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12 checkbox-areas">
                           <label class="label-shw"> select preferred contact method <span class="asteric">*</span> </label>
                           <ul class="list-inline areas-ul">
                              <li>
                                 <div class="checkbox checkbox-primary custom-checkbox">
                                    <input id="checkbox3" type="checkbox" >
                                    <label for="checkbox3">email address</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="checkbox checkbox-primary custom-checkbox">
                                    <input id="checkbox4" type="checkbox" >
                                    <label for="checkbox4">phone</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="checkbox checkbox-primary custom-checkbox">
                                    <input id="checkbox5" type="checkbox" >
                                    <label for="checkbox5">text message</label>
                                 </div>
                              </li>
                              <li>
                                 <div class="checkbox checkbox-primary custom-checkbox">
                                    <input id="checkbox6" type="checkbox" >
                                    <label for="checkbox6">hide my phone number</label>
                                 </div>
                              </li>
                           </ul>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="name">property amenities  <span class="asteric">*</span></label>
                              <div class="ui fluid selection search dropdown multiple selct-3">
                                 <input name="tags" type="hidden">
                                 <div class="default text">Select</div>
                                 <i class="dropdown icon"></i>
                                 <div class="menu">
                                    <div class="item" data-value="angular">Angular</div>
                                    <div class="item" data-value="css">CSS</div>
                                    <div class="item" data-value="design">Graphic Design</div>
                                    <div class="item" data-value="ember">Ember</div>
                                    <div class="item" data-value="html">HTML</div>
                                    <div class="item" data-value="ia">Information Architecture</div>
                                    <div class="item" data-value="javascript">Javascript</div>
                                    <div class="item" data-value="mech">Mechanical Engineering</div>
                                    <div class="item" data-value="meteor">Meteor</div>
                                    <div class="item" data-value="node">NodeJS</div>
                                    <div class="item" data-value="plumbing">Plumbing</div>
                                    <div class="item" data-value="python">Python</div>
                                    <div class="item" data-value="rails">Rails</div>
                                    <div class="item" data-value="react">React</div>
                                    <div class="item" data-value="repair">Kitchen Repair</div>
                                    <div class="item" data-value="ruby">Ruby</div>
                                    <div class="item" data-value="ui">UI Design</div>
                                    <div class="item" data-value="ux">User Experience</div>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                           <div class="form-group form-h">
                              <label for="city">community amenities  <span class="asteric">*</span></label>
                              <select class="form-control" id="city" class="city">
                                 <option></option>
                                 <option>london</option>
                                 <option>Delhi</option>
                                 <option>Paris</option>
                                 <option>perth</option>
                              </select>
                           </div>
                        </div>
                     </div>
                     <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                        <button class="btn btn-default previous-button  " type="button">PREVIOUS</button>
                        <button class="btn btn-default next-button nextBtn " type="button">NEXT</button>
                     </div>
                  </div>
                  <!--            <button class="btn btn-primary nextBtn pull-right" type="button">Next</button> -->
               </div>
               <div class="panel  setup-content" id="step-6">
                <div class="col-md-12 col-sm-12 col-xs-12 post-rental-tab">

                   <div class="sec_heading_div">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                           <h1 class="sec_heading_left">rental details</h1>
                        </div>
                     </div>


                      <div class="col-xs-12 col-sm-12 col-md-12 text-right next-div">
                        <button class="btn btn-default previous-button  " type="button">PREVIOUS</button>
                        <button class="btn btn-default next-button  " type="button">FINISH</button>
                     </div>

                 <!--  <button class="btn btn-primary nextBtn pull-right" type="button">Next</button> -->
               </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
@endsection