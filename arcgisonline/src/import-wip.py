
csvFilename = cnf.csvFilename
# Credentials and feature service information
username = cnf.username
password = cnf.password
service = cnf.service
fsURL = cnf.fsURL




def fillEmptyGeo(con, fsURL):
    """
    This function queries the service layer end points to ensure there is geometry as the
    script does an update on existing geometry.
    If there are no features, a dummy point is entered.
    """
        
    ptURL = fsURL + "/0/query"
    
    query_dict = {
        "f": "json",
        "where": "1=1",
        "token": con.token
    }

    # Check 1 point exists in the point layer (0), if not, add a value
    if (send_AGOL_Request(ptURL, query_dict, 1)) is False:
        
        ptGeoURL = fsURL + "/0/addFeatures"
        ptData = {
            "features": con.jsonPoint(0, 0, 1000000000),
            "f": "json",
            "token": con.token
        }
        
        if send_AGOL_Request(ptGeoURL, ptData):
            print("Inserted dummy point")
            
    return

    return


def updatePoint(con, ptURL, X, Y, ptTime):
    """Use a URL and X/Y values to update an existing point."""
 
    try:
        # Always updates point #1.
        submitData = {
            "features": con.jsonPoint(X, Y, ptTime),
            "f": "json",
            "token": con.token
        }
        
        jUpdate = send_AGOL_Request(ptURL, submitData)          
  
    except:
        print("couldn't update point")

    return



               
def logError (err):
   with open("foutieve adressen.txt", "a") as f:
            f.write(str(err))  
                    


if __name__ == "__main__": 



        cnt = 0;
        with open(csvFilename) as csvfile: #, 'rb'
            reader = csv.reader(csvfile, delimiter=';', quotechar='|')
            for row in reader:
                #check for lat and long values
                if (str(row[3]).replace('.','',1).replace(',','',1).isdigit() and str(row[4]).replace(',','',1).replace('.','',1).isdigit()):
                    Land = row[0]
                    Straat= row[1]  
                    if str(row[2]).replace('.','',1).replace(',','',1).isdigit():             
                        Nummer= int(row[2])
                    else:
                        Nummer = 0
                    Long = float(row[3].replace(',','.',1))
                    Lat = float(row[4].replace(',','.',1))
              
                   
                    jSON = {
                        "attributes": {
                            "Land":row[0],
                            "Straat":row[1],
                            "Nummer":Nummer,
                            "Long":Long,
                            "Lat": Lat,
                            "Postcode":row[5],
                            "Opdrachtgever":row[6],
                            "Plaats":row[7]
                        },
                        "geometry": {
                            "x": Long,
                            "y": Lat,
                            "spatialReference" : {"wkid" : 4326} 
                        }}                
                    addPoint(con, fsURL + "/0/addFeatures",jSON)
                    print ("{0} Adding feature {1}",cnt+1,row[1])
                           
                    cnt += 1
                
            # Generic exception handling: simple message is printed to the screen so the script continues to run.
        print ("Done publishing ",str(cnt)," features")
            # Additionally, an email or other action could be implemented below.
    except Exception as e:
        print("ERROR caught:  {0}".format(e))