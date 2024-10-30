/*
 Helper Functions to Build Visualizations

 @package    Marketing Performance
 @author     VyraSage
 @since      1.0.0
 @since      2.0.0 Added support for commissions based on click-through / order
 @license    GPL-3.0+
 @copyright  Copyright (c) 2019, VyraSage
*/

function drawLine( ctx, startX, startY, endX, endY, color, lineWidth ) {
	ctx.beginPath();
	ctx.moveTo( startX, startY );
	ctx.lineTo( endX, endY );
	ctx.strokeStyle = color;
	ctx.lineWidth = lineWidth;
	ctx.stroke();
}	

function drawArc( ctx, centerX, centerY, radius, startAngle, endAngle ) {
	ctx.beginPath();
	ctx.arc( centerX, centerY, radius, startAngle, endAngle );
	ctx.stroke();
}

function drawPieSlice( ctx, centerX, centerY, radius, startAngle, endAngle, color ) {
	ctx.fillStyle = color;
	ctx.beginPath();
	ctx.moveTo( centerX, centerY );
	ctx.arc( centerX, centerY, radius, startAngle, endAngle );
	ctx.closePath();
	ctx.fill();
}

var Piechart = function( options ) {

	this.canvas = options.canvas;
	this.ctx = options.canvas.getContext( '2d' );
	this.data = options.data;
	this.legend = options.legend;

	this.draw = function() {

		var total_value = 0;

		for ( var i = 0; i < this.data.length; i++ ) {
			total_value += this.data[i][1];
		}

		var start_angle = 0;
		for ( var i = 0; i < this.data.length; i++ ) {
			val = this.data[i][1];
			var slice_angle = 2 * Math.PI * val / total_value;

			drawPieSlice(
				this.ctx,
				this.canvas.width/2,
				this.canvas.height/2,
				Math.min( this.canvas.width/2.5, this.canvas.height/2.5 ),
				start_angle,
				start_angle+slice_angle,
				this.data[i][2]
			);

			start_angle += slice_angle;

		}

		this.ctx.fillStyle = 'black';
		this.ctx.font = 'bold 12px Arial';
		this.ctx.fillText( 'Attribution', 5, 15 );

		start_angle = 0;
		for ( var i = 0; i < this.data.length; i++ ) {
			val = this.data[i][1];
			slice_angle = 2 * Math.PI * val / total_value;
			var pieRadius = Math.min( this.canvas.width/2, this.canvas.height/2 );
			var labelX = this.canvas.width/2 + (pieRadius / 2) * Math.cos( start_angle + slice_angle/2 );
			var labelY = this.canvas.height/2 + (pieRadius / 2) * Math.sin( start_angle + slice_angle/2 );

			var labelText = Math.round( 100 * val / total_value );
			this.ctx.fillStyle = 'white';
			this.ctx.font = 'bold 8px Arial';
			this.ctx.fillText( labelText + '%', labelX, labelY );
			start_angle += slice_angle;
		}

		var legendHTML = '<div><p style="10px Arial;">';
		for ( var i = 0; i < this.data.length; i++ ) {
			legendHTML += '<span style="display:inline-block;width:16px;background-color:' + this.data[i][2] + ';">&nbsp;</span>' + this.data[i][0] + '&nbsp;&nbsp;';
		}
		legendHTML += '</p></div>';
		this.legend.innerHTML = legendHTML;
     
	}
}	

var Barchart = function( options ) {
	this.canvas = options.canvas;
	this.ctx = options.canvas.getContext( '2d' );
	this.data = options.data;
	this.title = options.title;

	this.draw = function() {
		var x = 5;
		var y = 30;
		var barSize = 10;
		var barLength = 0;

		var maxCount = 0;
		for ( var i = 0; i < this.data.length; i++ ) {
			if ( this.data[i][1] > maxCount ) {
				maxCount = this.data[i][1];
			}
		}

		this.ctx.fillStyle = "black";
		this.ctx.font = "bold 12px Arial";
		this.ctx.fillText( this.title, 5, 15 );

		for ( var i = 0; i < this.data.length; i++ ) {
			var channel = this.data[i][0];
			var count = this.data[i][1];
			var color = this.data[i][2];
			this.ctx.fillStyle = color;
			this.ctx.fillRect( x, y, (count / maxCount) * (this.canvas.width / 1.3), barSize );
			this.ctx.strokeStyle = 'black';
			this.ctx.strokeRect( x, y, (channel / maxCount) * (this.canvas.width / 1.3), barSize );
			this.ctx.font = 'bold 8px Arial';
			this.ctx.fillStyle = 'white';
			this.ctx.fillText( count, x + 5, y + 8 );
			this.ctx.fillStyle = 'black';
			this.ctx.fillText( channel, (count / maxCount) * (this.canvas.width / 1.3) + 10, y + 8 );

			y += 5 + barSize;
		}
	}
}



var Linechart = function( options ) {
	this.options = options;
	this.canvas = options.canvas;
	this.ctx = this.canvas.getContext( '2d' );
	this.data = options.data;
	this.color = options.color;
	this.lineWidth = options.lineWidth;

	function calcXY(pointXY) {
		var x = ( (pointXY[0] * 17.25) + 30 );
		var y = ( (pointXY[1] * -1.5) + 180 );
		return [x, y];
	}

	this.draw = function() {
		var x = 5;
		var y = 30;

		// X Axis
		fromPoint = calcXY( [0,0] );
		toPoint = calcXY( [9,0] );
		drawLine( this.ctx, fromPoint[0], fromPoint[1], toPoint[0], toPoint[1], 'black', 1 );
		// X Axis Tickmarks
		var incr = ( toPoint[0] - fromPoint[0] ) / 9;
		for ( var i = 1; i <= 9; i++ ) {
			drawLine( this.ctx, fromPoint[0] + (incr * i), fromPoint[1] + 2, fromPoint[0] + (incr * i), fromPoint[1], 'black', 1 );
			this.ctx.fillText( i, fromPoint[0] + (incr * i) - 3, fromPoint[1] + 10 );
		}

		// Y Axis
		fromPoint = calcXY( [0,0] );
		toPoint = calcXY( [0,100] );
		drawLine( this.ctx, fromPoint[0], fromPoint[1], toPoint[0], toPoint[1], 'black', 1 );
		// Y Axis Tickmarks
		var incr = (toPoint[1] - fromPoint[1]) / 10;
		for ( var i = 1; i <= 10; i++ ) {
			drawLine( this.ctx, fromPoint[0] - 2 , fromPoint[1] + (incr * i), fromPoint[0], fromPoint[1] + (incr * i), 'black', 1 );	
			this.ctx.fillText( i + '0%', fromPoint[0] - 30 , fromPoint[1] + (incr * i) + 2 );					
		}

		this.ctx.fillStyle = 'black';
		this.ctx.font = 'bold 12px Arial';
		this.ctx.fillText( this.options.title, 5, 15 );
		for ( var i = 0; i <= this.data.length; i++ ) {
			var toPoint = calcXY( [i, this.data[i]] );
			this.ctx.fillStyle = "grey";
			this.ctx.fillRect( toPoint[0]-3, toPoint[1]-3, 6, 6 );
			if ( i > 0 ) {
				var fromPoint = calcXY( [fromX, fromY] );
				drawLine( this.ctx, fromPoint[0], fromPoint[1], toPoint[0], toPoint[1], this.color, 2 );
			}
			var fromX = i;
			var fromY = this.data[i];
		}
	}
}