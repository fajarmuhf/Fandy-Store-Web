<?php
	session_start();
	include "../secure.php";
	include "../../../include/koneksi.php";
	$Koneksi = new Hubungi();
	$Koneksi->Konek("fandystore");
	$banyakindex = 3;

	function rupiah($angka){
	
		$hasil_rupiah = "Rp " . number_format($angka,2,',','.');
		return $hasil_rupiah;
	 
	}
				
	if(isset($_POST['start']) && @$_POST['start'] >= 0){
		$username = $_SESSION['username'];
		$password = $_SESSION['password'];
		$query="SELECT Id FROM `user-manager` WHERE Username = ? AND Password = ?";
		$exquery=$Koneksi->getKonek()->prepare($query);
		$exquery->bind_param("ss",$username,$password);
		$exquery->execute();
		date_default_timezone_set('Asia/Jakarta');
		$tanggal = date("Y-m-d H:i:s");
		if($exquery){
			$tampil=$exquery->get_result()->fetch_all(MYSQLI_ASSOC);

			$iduser = $tampil[0]["Id"];
		}

		$start = $_POST['start'];
		
		$tglawal = $_SESSION['tglbefore'];
		$tglakhir = $_SESSION['tglafter'];

		$tglawal2 = date_create($_SESSION['tglbefore']);
		$tglakhir2 = date_create($_SESSION['tglafter']);
		$diffent = date_diff($tglawal2,$tglakhir2);

		$query = "SELECT id FROM `user` WHERE id = ?";
		$exquery=$Koneksi->getKonek()->prepare($query);
		$exquery->bind_param("s",$id);
		$exquery->execute();
		if($exquery){
			$tampil=$exquery->get_result()->fetch_all(MYSQLI_ASSOC);

			$hasilkueh = mysqli_fetch_array($exkueh);
			$id_pemilik = $tampil[0]["Id"];
		}	
		
		$total_biaya_penyusutan = 0;

		$query = "SELECT * FROM `aset` WHERE id_pemilik = ?";
		$exquery=$Koneksi->getKonek()->prepare($query);
		$exquery->bind_param("i",$id_pemilik);
		$result = $exquery->execute();
		if($result){
			$hasil=$exquery->get_result()->fetch_all(MYSQLI_ASSOC);
			for($i=0;$i<count($hasil);$i++){
				$tglbeli = date_create(date("Y-m-d H:i:s",strtotime($hasil[$i]['tanggal_beli'])));
				$tglsekarang = date_create(date("Y-m-d H:i:s"));
				$diff  = date_diff( $tglbeli, $tglsekarang );
				$harga_sekarang = $hasil[$i]['harga_perolehan']-($diff->y+($diff->m)/12+($diff->d)/365)*($hasil[$i]['harga_perolehan']-$hasil[$i]['harga_sisa'])/$hasil[$i]['umur'];
				$total_biaya_penyusutan_per_hari = ($hasil[$i]['harga_perolehan']-$harga_sekarang)/365;
				//$total_biaya_penyusutan_per_hari = ($hasil[$i]['harga_perolehan']-$harga_sekarang)/365+$hasil[$i]['harga_perolehan']/($diff->y+($diff->m)/12+($diff->d)/365)/365;
				$total_biaya_penyusutan += $total_biaya_penyusutan_per_hari*($diffent->y*365+($diffent->m)*365/12+($diffent->d));
			}
		}

		$query = "SELECT * FROM `clientorder` WHERE updateorder >= '$tglawal' AND updateorder <= '$tglakhir' ORDER BY updateorder DESC ";
		$exquery=$Koneksi->getKonek()->prepare($query);
		$result = $exquery->execute();
		$pendapatantotal = 0;
		$omsettotal = 0;
		$modaltotal = 0;
		if($result){
			$html = "";
			$html .= "<div style='overflow-x: scroll;overflow-y: scroll;height: 480px;'>";
			$html .= "<table align=center border=1 id=tabelku class=CSSTableGenerator >
					<tr>
						<td style='width:6.5%;'>Id</td>
						<td style='width:6.5%;'>Tanggal</td>
						<td style='width:6.5%;'>Pelangan/Kasir</td>
						<td style='width:6.5%;'>Driver</td>
						<td style='width:35%;'>Rincian Barang </td>
						<td style='width:6.5%;'>Alamat</td>
						<td style='width:6.5%;'>Pembayaran</td>
						<td style='width:6.5%;'>Status</td>
						<td style='width:6.5%;'>Restock</td>
						<td style='width:6.5%;'>Uang Diterima</td>
						<td style='width:6.5%;'>Aksi</td></tr>
					";
			$hasil=$exquery->get_result()->fetch_all(MYSQLI_ASSOC);
			for($i=0;$i<count($hasil);$i++){
				$idorder = $hasil[$i]["id"];
				$html .= "<tr>
						<td style=\"font-size: large; font-weight: bold;\">".$hasil[$i]['id']."</td>";
				$query3 = "SELECT * FROM `cart` WHERE orderid = ?";
				$exquery3=$Koneksi->getKonek()->prepare($query3);
				$exquery3->bind_param("i",$idorder);
				$result3 = $exquery3->execute();
				if($result3){
					$hasil3=$exquery3->get_result()->fetch_all(MYSQLI_ASSOC);
					$rincianbarang = "";
					$totalbayar = 0;
					$totalmodal = 0;
					$pendapatan = 0;
					$omset = 0;
					for($j=0;$j<count($hasil3);$j++){
						if($hasil3[$j]["usermanagerid"]==0){
							if($j==0){
								$user_id = $hasil3[$j]["userid"];
								$query2 = "SELECT * FROM `user` WHERE id = $user_id";
								$exquery2 = mysqli_query($Koneksi->getKonek(),$query2);
								if($exquery2){
									$hasil2 = mysqli_fetch_array($exquery2);
									$user_name = $hasil2['nama'];
								}
							}
							$item_id = $hasil3[$j]["itemid"];
							$query2 = "SELECT * FROM `produk` WHERE id = $item_id";
							$exquery2 = mysqli_query($Koneksi->getKonek(),$query2);
							if($exquery2){
								$hasil2 = mysqli_fetch_array($exquery2);
								$barang_name = $hasil2['nama'];
								$barang_harga = $hasil2['harga'];
								$barang_modal = $hasil2['modal'];
								$total_barang = $hasil3[$j]["jumlah"]*$barang_harga;
								$total_modal = $hasil3[$j]["jumlah"]*$barang_modal;
								$totalbayar += $total_barang;
								$totalmodal += $total_modal;
								$pendapatan += $total_barang-($total_modal);
								$omset += $total_barang;
								$rincianbarang .= $barang_name." - ".$hasil3[$j]["jumlah"]." x ".rupiah($barang_harga)." = ".rupiah($total_barang)."<br>";
							}
						}
						else{
							if($j==0){
								$user_id = $hasil3[$j]["usermanagerid"];
								$query2 = "SELECT * FROM `user-manager` WHERE id = $user_id";
								$exquery2 = mysqli_query($Koneksi->getKonek(),$query2);
								if($exquery2){
									$hasil2 = mysqli_fetch_array($exquery2);
									$user_name = $hasil2['Username'];
									$perum = $hasil2["Perum"];
									$query22 = "SELECT * FROM `perumahan` WHERE nama = '$perum'";
									$exquery22 = mysqli_query($Koneksi->getKonek(),$query22);
									if($exquery22){
										$hasil22 = mysqli_fetch_array($exquery22);
										$alamat= $perum." ".$hasil22["lokasi"];
									}	
								}
							}

							$item_id = $hasil3[$j]["itemid"];
							$query2 = "SELECT * FROM `produk` WHERE id = $item_id";
							$exquery2 = mysqli_query($Koneksi->getKonek(),$query2);
							if($exquery2){
								$hasil2 = mysqli_fetch_array($exquery2);
								$barang_name = $hasil2['nama'];
								$barang_harga = $hasil2['harga'];
								$barang_modal = $hasil2['modal'];
								$total_barang = $hasil3[$j]["jumlah"]*$barang_harga;
								$total_modal = $hasil3[$j]["jumlah"]*$barang_modal;
								$totalbayar += $total_barang;
								$totalmodal += $total_modal;
								$pendapatan += $total_barang-($total_modal);
								$omset += $total_barang;
								$rincianbarang .= $barang_name." - ".$hasil3[$j]["jumlah"]." x ".rupiah($barang_harga)." = ".rupiah($total_barang)."<br>";
							}
						}
					}
					$biayaongkir = $hasil[$i]["biayaongkir"];
					$totalbayar += $biayaongkir;
					$kodeunik = $hasil[$i]["kodeunik"];
					if($kodeunik!=""){
						$rincianbarang .= "<b style=\"color: purple;\">Kode Unik = ".$kodeunik."<br>";
						$totalbayar += $kodeunik;
						$omset += $kodeunik;
						$pendapatan += $kodeunik;
					}
					$pendapatantotal += $pendapatan;
					$modaltotal += $totalmodal;
					$omsettotal += $omset;
					$rincianbarang .= "<br><b style=\"color: coral;\">Biaya Ongkir = ".rupiah($biayaongkir)."<br>";
					$rincianbarang .= "<b style=\"color: red;\">SubTotal Modal = ".rupiah($totalmodal)."<br>";
					$rincianbarang .= "<b style=\"color: blue;\">SubTotal Bayar = ".rupiah($totalbayar)."<br>";
					$rincianbarang .= "<b style=\"color: green;\">Pendapatan = ".rupiah($pendapatan)."<br>";
					$rincianbarang .= "<b style=\"color: magenta;\">Omset = ".rupiah($omset);
				}
				if($hasil[$i]["driverid"]==NULL){
					$driver="";
				}
				else{
					$driver_id = $hasil[$i]["driverid"];
					$query2 = "SELECT * FROM `driver` WHERE id = $driver_id";
					$exquery2 = mysqli_query($Koneksi->getKonek(),$query2);
					if($exquery2){
						$hasil2 = mysqli_fetch_array($exquery2);
						$driver_name = $hasil2['nama'];
					}
					$driver = $driver_id." - ".$driver_name;
				}
				if($hasil[$i]["alamat"]!=""){
					$alamat = $hasil[$i]["alamat"];
				}
				$status = $hasil[$i]["status"];
				$restock = $hasil[$i]["restock"];
				$uang_diterima = $hasil[$i]["uang_diterima"];
				$pembayaran = $hasil[$i]["pembayaran"];
				$tanggalupdate = $hasil[$i]["updateorder"];
				$html .= "<td style=\"font-size: large; font-weight: bold;\">".$tanggalupdate."</td>";
				$html .= "<td style=\"font-size: large; font-weight: bold;\">".$user_id." - ".$user_name."</td>";
				$html .= "<td style=\"font-size: large; font-weight: bold;\">".$driver."</td>";
				$html .= "<td style=\"font-size: small; font-weight: bold;\">".$rincianbarang."</td>";
				$html .= "<td style=\"font-size: large; font-weight: bold;\">".$alamat."</td>";
				$html .= "<td style=\"font-size: large; font-weight: bold;\">".$pembayaran."</td>";
				if($status=="Dibatalkan")$html .= "<td style=\"font-size: large; font-weight: bold;color: red;\">".$status."</td>";
				else if($status=="selesai")$html .= "<td style=\"font-size: large; font-weight: bold;color: green;\">".$status."</td>";
				else $html .= "<td style=\"font-size: large; font-weight: bold;\">".$status."</td>";
				
				if($restock=="sudah")$html .= "<td style=\"font-size: large; font-weight: bold;\"><img src=\"page/penjualan/image/checklist.png\" alt=\"Sudah direstock\" width=\"50\" height=\"50\"></td>";
				else if($restock=="")$html .= "<td style=\"font-size: large; font-weight: bold;\"></td>";
				
				if($uang_diterima=="sudah")$html .= "<td style=\"font-size: large; font-weight: bold;\"><img src=\"page/penjualan/image/checklist.png\" alt=\"Sudah diterima\" width=\"50\" height=\"50\"></td>";
				else if($uang_diterima=="")$html .= "<td style=\"font-size: large; font-weight: bold;\"><img src=\"page/penjualan/image/cross.png\" alt=\"Belum diterima\" width=\"50\" height=\"50\"></td>";
						
				
				if($status=="Dibatalkan"){
					if($restock=="sudah"){
						$html .= "
						<td>
							<select id='Aksi".$hasil[$i]['id']."' name='Aksi".$hasil[$i]['id']."' onChange=document.getElementById('Aksi".$hasil[$i]['id']."').name='Aksi';document.getElementById('identitas').value='".$hasil[$i]['id']."';document.getElementById('daftar').submit() >
								<option value=''>--pilih aksi--</option>
								<option value='hapus2'>Hapus</option>
							</select>
						</td>
						</tr>";
					}
					else{
						$html .= "
						<td>
							<select id='Aksi".$hasil[$i]['id']."' name='Aksi".$hasil[$i]['id']."' onChange=document.getElementById('Aksi".$hasil[$i]['id']."').name='Aksi';document.getElementById('identitas').value='".$hasil[$i]['id']."';document.getElementById('daftar').submit() >
								<option value=''>--pilih aksi--</option>
								<option value='hapus2'>Hapus</option>
								<option value='restock'>Restock</option>
							</select>
						</td>
						</tr>";
					}
				}else{
					if($uang_diterima=="sudah"){
						$html .= "
						<td>
							<select id='Aksi".$hasil[$i]['id']."' name='Aksi".$hasil[$i]['id']."' onChange=document.getElementById('Aksi".$hasil[$i]['id']."').name='Aksi';document.getElementById('identitas').value='".$hasil[$i]['id']."';document.getElementById('daftar').submit() >
								<option value=''>--pilih aksi--</option>
								<option value='hapus2'>Hapus</option>
							</select>
						</td>
						</tr>";
					}
					else{
						$html .= "
						<td>
							<select id='Aksi".$hasil[$i]['id']."' name='Aksi".$hasil[$i]['id']."' onChange=document.getElementById('Aksi".$hasil[$i]['id']."').name='Aksi';document.getElementById('identitas').value='".$hasil[$i]['id']."';document.getElementById('daftar').submit() >
								<option value=''>--pilih aksi--</option>
								<option value='hapus2'>Hapus</option>
								<option value='terimauang'>Terima Uang</option>
							</select>
						</td>
						</tr>";
					}
				}	
			
			}
			$pendapatantotal -= $total_biaya_penyusutan;
			$html .= "</table>";
			$html .= "</div>";
			$html .= "<table align=center border=1 id=tabelku2 class=CSSTableGenerator >";
			$html .= "<tr>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;''></th>
						<th style='width:6.5%;'></th>
						<th style='width:7.25%;'></th>
						<th style='width:35%;'></th>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;'></th>
						<th style='width:6.5%;'></th>
					</tr>
					<tr>
						<td colspan='4' style=\"text-align:center;font-size: large;\">Modal<br>Biaya Penyusutan Aset<br>Pendapatan Kotor<br>Pendapatan Bersih<br>Omset</td>
						<td colspan='7' style=\"text-align:left;font-size: large;\">".rupiah($modaltotal)."<br>".rupiah($total_biaya_penyusutan)."<br>".rupiah($pendapatantotal+$total_biaya_penyusutan)."<br>".rupiah($pendapatantotal)."<br>".rupiah($omsettotal)."</td>
					</tr>";
			$html .= "</table>";
			echo $html;
		}
	}
?>